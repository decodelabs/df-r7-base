<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\socket\streams;

use df;
use df\core;
use df\link;

use DecodeLabs\Glitch;

// Client
class Tcp_Client extends link\socket\Client implements link\socket\ISequenceClientSocket, link\socket\ISecureClientSocket
{
    use link\socket\TSequenceClientSocket;
    use link\socket\TSecureClientSocket;
    use TStreams;
    use TStreams_IoSocket;

    const DEFAULT_OPTIONS = [
        'oobInline' => false
    ];

    protected static function _populateOptions()
    {
        return array_merge(parent::_populateOptions(), self::DEFAULT_OPTIONS);
    }

    public function getId(): string
    {
        if (!$this->_id) {
            if (!$this->_isConnected) {
                $this->connect();

                /*
                throw new link\socket\RuntimeException(
                    'Client sockets cannot generate an ID before they are connected'
                );
                */
            }

            $this->_id = $this->_address.'|'.stream_socket_get_name($this->_socket, false);
        }

        return $this->_id;
    }


    // Operation
    protected function _connectPeer()
    {
        $options = [];

        if ($this->_isSecure) {
            if ($this->_secureOnConnect) {
                $address = $this->_address->toString($this->getSecureTransport());
            } else {
                $address = $this->_address->toString('tcp');
            }

            $options['ssl'] = $this->_secureOptions;
        } else {
            $address = $this->_address->toString();
        }

        try {
            $context = stream_context_create($options);
            $socket = stream_socket_client(
                $address,
                $errorNumber,
                $this->_lastError,
                $this->_getOption('connectionTimeout'),
                STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT,
                $context
            );

            if ($rTimeout = $this->_getOption('receiveTimeout')) {
                stream_set_timeout($socket, 0, $rTimeout * 1000);
            }
        } catch (\Throwable $e) {
            $this->_lastError = $e->getMessage();

            throw new link\socket\ConnectionException(
                'Could not connect client to '.$this->_address.' - '.$this->_getLastErrorMessage()
            );
        }

        return $socket;
    }

    protected function _connectPair()
    {
        switch ($this->_address->getSocketDomain()) {
            case 'inet':
                $domain = STREAM_PF_INET;
                break;

            case 'inet6':
                $domain = STREAM_PF_INET6;
                break;

            case 'unix':
                $domain = STREAM_PF_UNIX;
                break;

            default:
                throw Glitch::EUnexpectedValue('Unsupported socket domain: '.$this->_address->getSocketDomain());
        }

        return stream_socket_pair(
            $domain,
            $this->_useSeqPackets ? STREAM_SOCK_SEQPACKET : STREAM_SOCK_STREAM,
            STREAM_IPPROTO_TCP
        );
    }

    public function checkConnection()
    {
        if (!is_resource($this->_socket)) {
            return false;
        }

        $info = stream_get_meta_data($this->_socket);
        if ($info['timed_out']) {
            return false;
        }

        if (stream_socket_get_name($this->_socket, true) === false) {
            return false;
        }

        //stream_socket_recvfrom($this->_socket, 1, STREAM_PEEK);
        return !feof($this->_socket);
    }
}


// Server
class Tcp_Server extends link\socket\Server implements link\socket\ISequenceServerSocket, link\socket\ISecureServerSocket
{
    use link\socket\TSecureServerSocket;
    use link\socket\TSequenceServerSocket;
    use TStreams;

    const DEFAULT_OPTIONS = [
        'oobInline' => false
    ];

    protected static function _populateOptions()
    {
        return array_merge(parent::_populateOptions(), self::DEFAULT_OPTIONS);
    }


    // Operation
    protected function _startListening()
    {
        $options = [
            'socket' => [
                'backlog' => $this->getConnectionQueueSize()
            ]
        ];

        if ($this->_isSecure) {
            if ($this->_secureOnConnect) {
                $address = $this->_address->toString($this->getSecureTransport());
            } else {
                $address = $this->_address->toString('tcp');
            }

            $options['ssl'] = $this->_secureOptions;
        } else {
            $address = $this->_address->toString();
        }


        try {
            $context = stream_context_create($options);
            $this->_socket = @stream_socket_server(
                $address,
                $errorNumber,
                $this->_lastError,
                STREAM_SERVER_BIND | STREAM_SERVER_LISTEN,
                $context
            );
        } catch (\Throwable $e) {
            throw new link\socket\ConnectionException(
                'Could not create socket on '.$this->_address.' - '.$this->_getLastErrorMessage()
            );
        }
    }

    protected function _acceptSequencePeer()
    {
        try {
            $output = stream_socket_accept($this->_socket);
        } catch (\Throwable $e) {
            $this->_lastError = $e->getMessage();
            return false;
        }

        return $output;
    }

    protected function _getPeerAddress($socket)
    {
        return $this->_address->getScheme().'://'.stream_socket_get_name($socket, true);
    }


    public function checkConnection()
    {
        return is_resource($this->_socket)
            && (stream_socket_get_name($this->_socket, false) !== false);
    }
}



// Server peer
class Tcp_ServerPeer extends link\socket\ServerPeer implements link\socket\ISequenceServerPeerSocket, link\socket\ISecureServerPeerSocket
{
    use link\socket\TSequenceServerPeerSocket;
    use link\socket\TSecureServerPeerSocket;
    use TStreams;
    use TStreams_IoSocket;

    protected static function _populateOptions()
    {
        return [];
    }

    public function __construct(link\socket\IServerSocket $parent, $socket, $address)
    {
        parent::__construct($parent, $socket, $address);

        if ($parent instanceof link\socket\ISecureSocket) {
            $this->_isSecure = $parent->isSecure();
            $this->_secureTransport = $parent->getSecureTransport();
        } else {
            $this->_isSecure = false;
            $this->_secureTransport = null;
        }
    }


    // Operation
    public function checkConnection()
    {
        if (!is_resource($this->_socket)) {
            return false;
        }

        //stream_socket_recvfrom($this->_socket, 1, STREAM_PEEK);
        return !feof($this->_socket);
    }
}
