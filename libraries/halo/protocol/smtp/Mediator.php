<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\protocol\smtp;

use df;
use df\core;
use df\halo;
use df\flow;
    
class Mediator implements IMediator {

    const CONNECTION_TIMEOUT = 30;

    protected $_heloSent = false;
    protected $_authenticated = false;
    protected $_mailSent = false;
    protected $_rcptSent = false;
    protected $_dataSent = false;
    protected $_heloHost = '127.0.0.1';
    protected $_socket;

    public function __construct($dsn, $heloHost=null) {
        if($heloHost !== null) {
            $this->_heloHost = $heloHost;
        }

        if($dsn !== null) {
            $this->connect($dsn, $heloHost);
        }
    }

    public function getConnectionId() {
        if(!$this->_socket) {
            return null;
        }

        $address = $this->_socket->getAddress();
        return md5($address);
    }

    public function connect($dsn, $heloHost=null) {
        if($this->_socket) {
            $this->quit();
        }

        if($heloHost !== null) {
            $this->_heloHost = $heloHost;
        }

        $dsn = halo\socket\address\Inet::factory($dsn);
        $port = $dsn->getPort();
        $security = $dsn->getSecureTransport();

        if($port === null) {
            if($security == 'ssl') {
                $port = 465;
            } else {
                $port = 25;
            }

            $dsn->setPort($port);
        }

        $this->_socket = halo\socket\Client::factory($dsn)
            ->setConnectionTimeout(self::CONNECTION_TIMEOUT)
            ->shouldBlock(true)
            ->shouldSecureOnConnect($security != 'tls')
            ->connect();

        $response = $this->_readResponse(220);
        $response = $this->_ehlo();

        if($security == 'tls') {
            $this->sendRequest('STARTTLS', 220);
            $this->_socket->enableSecureTransport();
            $this->_ehlo();
        }

        $this->_heloSent = true;
        return $this;
    }

    protected function _ehlo() {
        try {
            return $this->sendRequest('EHLO '.$this->_heloHost, 250);
        } catch(IException $e) {
            return $this->sendRequest('HELO '.$this->_heloHost, 250);
        }
    }

    public function sendRequest($request, $responseCode) {
        if(!$this->_socket) {
            throw new LogicException(
                'POP3 socket has not been connected'
            );
        }

        if(!$this->_socket->writeLine($request)) {
            throw new RuntimeException(
                'Send failed, connection error'
            );
        }

        return $this->_readResponse($responseCode);
    }

    protected function _readResponse($code) {
        if(!$this->_socket) {
            throw new LogicException(
                'SMTP socket has not been connected'
            );
        }

        if(!is_array($code)) {
            $code = array($code);
        }

        $output = new Response();

        do {
            if($output->code) {
                $parts = explode(' ', $output->message, 2);
                $key = trim(array_shift($parts));
                $value = array_shift($parts);

                if($value === null) {
                    $value = $key;
                }

                $output->info[$key] = [
                    'code' => $output->code,
                    'value' => $value
                ];
            }

            $result = $this->_socket->readLine();

            if($result === false) {
                throw new UnexpectedValueException(
                    'Read failed, no data sent'
                );
            }

            list($command, $more, $message) = preg_split('/([\s-]+)/', $result, 2, PREG_SPLIT_DELIM_CAPTURE);

            if(!empty($error)) {
                $error .= ' '.$message;
            } else if($command === null || !in_array($command, $code)) {
                $error = $message;
            }

            $output->code = $command;
            $output->message = $message;
        } while(strpos($more, '-') === 0);

        if(!empty($error)) {
            throw new UnexpectedValueException($error, $output);
        }

        return $output;
    }

    public function login($user, $password, $authType=IMediator::LOGIN) {
        if(!$this->_heloSent) {
            throw new LogicException(
                'Cannot log in, HELO has not been sent'
            );
        }

        if($this->_authenticated) {
            throw new LogicException(
                'SMTP connection is already authenticated'
            );
        }

        switch($authType) {
            case IMediator::CRAMMD5:
                $challenge = $this->sendRequest('AUTH CRAM-MD5', 334);
                $challenge = base64_decode($challenge);
                $digest = $this->_hmacMd5($password, $challenge);
                $this->sendRequest(base64_encode($user.' '.$digest), 235);
                break;

            case IMediator::LOGIN:
                $this->sendRequest('AUTH LOGIN', 334);
                $this->sendRequest(base64_encode($user), 334);
                $this->sendRequest(base64_encode($password), 235);
                break;

            case IMediator::PLAIN:
                $this->sendRequest('AUTH PLAIN', 334);
                $this->sendRequest(base64_encode("\0".$user."\0".$password), 235);
                break;

            default:
                throw new InvalidArgumentException(
                    $authType.' is not a recognized auth type'
                );
        }

        $this->_authenticated = true;
        return $this;
    }

    protected function _hmacMd5($key, $data, $block=64) {
        if(strlen($key) > 64) {
            $key = pack('H32', md5($key));
        } else if(strlen($key) < 64) {
            $key = str_pad($key, $block, "\0");
        }

        $k_ipad = substr($key, 0, 64) ^ str_repeat(chr(0x36), 64);
        $k_opad = substr($key, 0, 64) ^ str_repeat(chr(0x5C), 64);

        $inner = pack('H32', md5($k_ipad . $data));
        $digest = md5($k_opad.$inner);

        return $digest;
    }

    public function sendFromAddress($address) {
        if(!$this->_authenticated) {
            throw new LogicException(
                'Connection must be authenticated before sending mail data'
            );
        }

        if($address instanceof flow\mail\IAddress) {
            $address = $address->getAddress();
        }

        $this->sendRequest('MAIL FROM:<'.$address.'>', 250);
        $this->_mailSent = true;
        $this->_rcptSent = false;
        $this->_dataSent = false;

        return $this;
    }

    public function sendRecipientAddress($address) {
        if(!$this->_authenticated) {
            throw new LogicException(
                'Connection must be authenticated before sending mail data'
            );
        }

        if(!$this->_mailSent) {
            throw new LogicException(
                'From address must be sent before recipient addresses'
            );
        }

        if($address instanceof flow\mail\IAddress) {
            $address = $address->getAddress();
        }

        $this->sendRequest('RCPT TO:<'.$address.'>', [250, 251]);
        $this->_rcptSent = true;

        return $this;
    }

    public function sendData($data) {
        if(!$this->_authenticated) {
            throw new LogicException(
                'Connection must be authenticated before sending mail data'
            );
        }

        if(!$this->_rcptSent) {
            throw new LogicException(
                'Recipient addresses must be sent before mail data'
            );  
        }

        $this->sendRequest('DATA', 354);

        foreach(explode(flow\mime\IPart::LINE_END, $data) as $line) {
            if(substr($line, 0, 1) == '.') {
                $line = '.'.$line;
            }

            $this->_socket->writeLine($line);
        }

        $this->sendRequest('.', 250);
        $this->_dataSent = true;

        return $this;
    }

    public function verifyUser($user) {
        if(!$this->_authenticated) {
            throw new LogicException(
                'Connection must be authenticated before users can be verified'
            );
        }

        $this->sendRequest('VRFY '.$user, [250, 251, 252]);
        return $this;
    }

    public function reset() {
        $this->sendRequest('RSET', [250, 220]);

        $this->_mailSent = false;
        $this->_rcptSent = false;
        $this->_dataSent = false;

        return $this;
    }

    public function noOp() {
        $this->sendRequest('NOOP', 250);
        return $this;
    }

    public function quit() {
        $this->sendRequest('QUIT', 221);
        $this->_socket->close();
        $this->_socket = null;

        $this->_heloHost = false;
        $this->_authenticated = false;
        $this->_mailSent = false;
        $this->_rcptSent = false;
        $this->_dataSent = false;

        return $this;
    }
}