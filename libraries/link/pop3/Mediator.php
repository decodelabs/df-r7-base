<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\pop3;

use df;
use df\core;
use df\link;

class Mediator implements IMediator {

    const CONNECTION_TIMEOUT = 30;

    protected $_socket;
    protected $_timestamp;
    protected $_isLoggedIn = false;

    public function __construct($dsn) {
        if($dsn !== null) {
            $this->connect($dsn);
        }
    }

    public function connect($dsn) {
        if($this->_socket) {
            $this->quit();
        }

        $dsn = link\socket\address\Inet::factory($dsn);
        $port = $dsn->getPort();
        $security = $dsn->getSecureTransport();

        if($port === null) {
            if($security == 'ssl') {
                $port = 995;
            } else {
                $port = 110;
            }

            $dsn->setPort($port);
        }

        $this->_socket = link\socket\Client::factory($dsn)
            ->setConnectionTimeout(self::CONNECTION_TIMEOUT)
            ->shouldBlock(true)
            ->shouldSecureOnConnect($security != 'tls')
            ->connect();

        $welcome = $this->_readResponse();

        strtok($welcome, '<');
        $this->_timestamp = strtok('>');

        if(!strpos($this->_timestamp, '@')) {
            $this->_timestamp = null;
        } else {
            $this->_timestamp = '<'.$this->_timestamp.'>';
        }

        if($security == 'tls') {
            $this->sendRequest('STLS');
            $this->_socket->enableSecureTransport();
        }

        return $this;
    }

    public function getConnectionId() {
        if(!$this->_socket) {
            return null;
        }

        $address = $this->_socket->getAddress();
        return md5($address);
    }

    public function sendRequest($request, $multiLine=false) {
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

        return $this->_readResponse($multiLine);
    }

    protected function _readResponse($multiLine=false) {
        if(!$this->_socket) {
            throw new LogicException(
                'POP3 socket has not been connected'
            );
        }

        $result = $this->_socket->readLine();

        if($result === false) {
            throw new UnexpectedValueException(
                'Read failed, no data sent'
            );
        }

        $parts = explode(' ', ltrim($result), 2);
        $status = trim(array_shift($parts));
        $message = array_shift($parts);

        if($status != '+OK') {
            throw new UnexpectedValueException(
                'Request failed - '.$message
            );
        }

        if($multiLine) {
            $message = '';
            $line = $this->_socket->readLine();

            while($line !== false && rtrim($line) != '.') {
                if($line{0} == '.') {
                    $line = substr($line, 1);
                }

                $message .= $line;
                $line = $this->_socket->readLine();
            }
        }

        return $message;
    }



    public function getCapabilities() {
        $result = $this->sendRequest('CAPA', true);
        return $this->_splitList($result);
    }

    public function login($user, $password) {
        $this->_isLoggedIn = false;

        if($this->_timestamp) {
            try {
                $this->sendRequest('APOP '.$user.' '.md5($this->_timestamp.$password));
                $this->_isLoggedIn = true;
            } catch(\Throwable $e) {}
        }

        if(!$this->_isLoggedIn) {
            $this->sendRequest('USER '.$user);
            $this->sendRequest('PASS '.$password);
            $this->_isLoggedIn = true;
        }

        return $this;
    }

    public function getStatus() {
        if(!$this->_isLoggedIn) {
            throw new LogicException(
                'Cannot call STAT before logging in'
            );
        }

        $result = $this->sendRequest('STAT');
        $parts = explode(' ', $result, 2);

        return [
            'messages' => (int)array_shift($parts),
            'size' => (int)array_shift($parts)
        ];
    }

    public function getSizeList() {
        if(!$this->_isLoggedIn) {
            throw new LogicException(
                'Cannot call LIST before logging in'
            );
        }

        $result = $this->sendRequest('LIST', true);
        $output = [];

        foreach(explode("\n", $result) as $line) {
            $line = trim($line);

            if(empty($line)) {
                continue;
            }

            $parts = explode(' ', $line, 2);
            $output[(int)array_shift($parts)] = (int)array_shift($parts);
        }

        return $output;
    }

    public function getUniqueIdList() {
        if(!$this->_isLoggedIn) {
            throw new LogicException(
                'Cannot call UIDL before logging in'
            );
        }

        $result = $this->sendRequest('UIDL', true);
        $output = [];

        foreach(explode("\n", $result) as $line) {
            $line = trim($line);

            if(empty($line)) {
                continue;
            }

            $parts = explode(' ', $line, 2);
            $output[(int)array_shift($parts)] = array_shift($parts);
        }

        return $output;
    }

    public function getTop($key, $lines=0) {
        if(!$this->_isLoggedIn) {
            throw new LogicException(
                'Cannot call TOP before logging in'
            );
        }

        $lines = (!$lines || $lines < 1) ? 0 : (int)$lines;
        return $this->sendRequest('TOP '.$key.' '.$lines, true);
    }

    public function getSize($key) {
        if(!$this->_isLoggedIn) {
            throw new LogicException(
                'Cannot call LIST before logging in'
            );
        }

        $result = $this->sendRequest('LIST '.$key);
        $parts = explode(' ', trim($result), 2);
        return (int)array_pop($parts);
    }

    public function getUniqueId($key) {
        if(!$this->_isLoggedIn) {
            throw new LogicException(
                'Cannot call UIDL before logging in'
            );
        }

        $result = $this->sendRequest('UIDL '. $key);
        $parts = explode(' ', trim($result), 2);
        return array_pop($parts);
    }

    public function getMail($key) {
        if(!$this->_isLoggedIn) {
            throw new LogicException(
                'Cannot call RETR before logging in'
            );
        }

        return $this->sendRequest('RETR '.$key, true);
    }

    public function deleteMail($key) {
        if(!$this->_isLoggedIn) {
            throw new LogicException(
                'Cannot call DELE before logging in'
            );
        }

        $this->sendRequest('DELE '.$key);
        return $this;
    }

    public function rollback() {
        if(!$this->_isLoggedIn) {
            throw new LogicException(
                'Cannot call RSET before logging in'
            );
        }

        $this->sendRequest('RSET');
        return $this;
    }

    public function noOp() {
        $this->sendRequest('NOOP');
        return $this;
    }

    public function quit() {
        $this->sendRequest('QUIT');
        $this->_isLoggedIn = false;
        $this->_socket->close();
        $this->_socket = null;

        return $this;
    }

    protected function _splitList($result) {
        $output = [];

        foreach(explode("\n", $result) as $line) {
            $line = rtrim($line, "\r\n");

            if(!empty($line)) {
                $output[] = $line;
            }
        }

        return $output;
    }
}