<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\socket;

use df;
use df\core;
use df\link;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

abstract class Client extends Base implements IClientSocket, Inspectable
{
    use TIoSocket;

    const DEFAULT_OPTIONS = [
        'connectionTimeout' => null
    ];

    protected $_isConnected = false;

    public static function factory($address, $useStreams=false)
    {
        $address = link\socket\address\Base::factory($address);

        if ($address instanceof IClientSocket) {
            return $address;
        }

        if (!$useStreams
        && (!extension_loaded('sockets') || $address->getSecureTransport())) {
            $useStreams = true;
        }

        $class = self::_getClass($address, $useStreams);
        return new $class($address);
    }

    protected static function _getClass(link\socket\address\IAddress $address, $useStreams=false)
    {
        $class = null;
        $protocol = ucfirst($address->getScheme());
        $nativeClass = 'df\\link\\socket\\native\\'.$protocol.'_Client';
        $streamsClass = 'df\\link\\socket\\streams\\'.$protocol.'_Client';

        if (!$useStreams) {
            if (class_exists($nativeClass)) {
                $class = $nativeClass;
            } elseif (class_exists($streamsClass)) {
                $class = $streamsClass;
            }
        } else {
            if (class_exists($streamsClass)) {
                $class = $streamsClass;
            } elseif ($protocol != 'Tcp' && class_exists($nativeClass)) {
                $class = $nativeClass;
            }
        }

        if (!$class) {
            throw new RuntimeException(
                'Protocol '.$address->getScheme().', whilst valid, does not yet have a client handler class'
            );
        }

        return $class;
    }

    protected static function _populateOptions()
    {
        $output = array_merge(parent::_populateOptions(), self::DEFAULT_OPTIONS);

        if (!isset($output['connectionTimeout']) || $output['connectionTimeout'] === null) {
            $output['connectionTimeout'] = ini_get('default_socket_timeout');
        }

        return $output;
    }

    // Options
    public function setConnectionTimeout($timeout)
    {
        return $this->_setOption('connectionTimeout', $timeout);
    }

    public function getConnectionTimeout()
    {
        return $this->_getOption('connectionTimeout');
    }


    // Operation
    public function isConnected()
    {
        return $this->_isConnected;
    }

    public function connect()
    {
        if ($this->_isConnected) {
            return $this;
        }

        $this->_socket = $this->_connectPeer();
        $this->_isConnected = true;

        $this->_setBlocking($this->_shouldBlock);

        $this->_readingEnabled = true;
        $this->_writingEnabled = true;

        return $this;
    }

    abstract protected function _connectPeer();


    public function connectPair()
    {
        if ($this->_isConnected) {
            throw new RuntimeException(
                'Cannot connect pair, socket is already established'
            );
        }

        //$resources = $this->_connectPair();
        Glitch::incomplete($this);
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        if ($this->_isConnected) {
            $output = $this->getId();
        } else {
            $output = $this->_address;
        }

        $output .= ' (';
        $args = [];

        if ($this->_isConnected) {
            if ($this->_readingEnabled) {
                $args[] = 'r';
            }

            if ($this->_writingEnabled) {
                $args[] = 'w';
            }
        }

        if (empty($args)) {
            $args[] = 'x';
        }

        if ($this instanceof ISecureSocket && $this->isSecure()) {
            array_unshift($args, 's');
        }

        $output .= implode('/', $args).')';
        $entity->setText($output);
    }
}
