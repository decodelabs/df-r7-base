<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\socket\address;

use df;
use df\core;
use df\link;

class Unix extends Base implements IUnixAddress {

    use core\uri\TUrl_PathContainer;

    public static function factory($address) {
        if($address instanceof IUnixAddress) {
            return $address;
        }

        return new self($address);
    }

    public function import($address='') {
        if($address !== null) {
            $this->reset();
        }

        if($address == '' || $address === null) {
            return $this;
        }

        if($address instanceof IUnixAddress) {
            $this->_scheme = $address->_scheme;

            if($address->_path) {
                $this->_path = clone $address->_path;
            }

            return $this;
        }

        $parts = explode('://', $address, 2);
        $address = array_pop($parts);
        $this->setScheme(array_shift($parts));

        $this->setPath($address);

        return $this;
    }

    public function reset() {
        $this->_resetScheme();
        $this->_resetPath();

        return $this;
    }

    public function __get($member) {
        switch($member) {
            case 'scheme':
                return $this->getScheme();

            case 'path':
                return $this->getPath();
        }
    }

    public function __set($member, $value) {
        switch($member) {
            case 'scheme':
                return $this->getScheme($value);

            case 'path':
                return $this->setPath($value);
        }
    }


// Scheme
    public function setScheme($scheme) {
        if(!strlen($scheme)) {
            $scheme = 'unix';
        }

        $scheme = strtolower($scheme);

        switch($scheme) {
            case 'unix':
            case 'udg':
                $this->_scheme = $scheme;
                break;

            default:
                $this->_scheme = 'unix';
                break;
        }

        return $this;
    }

    public function getScheme() {
        if(!$this->_scheme) {
            return 'unix';
        }

        return $this->_scheme;
    }


// Type
    public function getSocketDomain() {
        return 'unix';
    }

    public function getDefaultSocketType() {
        if($this->_scheme == 'udg') {
            return 'datagram';
        } else if($this->_scheme == 'unix') {
            return 'stream';
        }

        throw new link\socket\InvalidArgumentException(
            'Protocol '.$this->_scheme.' is not currently supported'
        );
    }


// Path
    public function setPath($path) {
        if(is_null($path) || is_string($path) && !strlen($path)) {
            $this->_path = null;
        } else {
            $this->_path = core\uri\FilePath::factory($path);
        }

        return $this;
    }

    public function getPath() {
        if(!$this->_path) {
            $this->_path = new core\uri\FilePath();
        }

        return $this->_path;
    }

    public function getPathString() {
        return $this->getPath()->toString();
    }


// Strings
    public function toString(): string {
        return $this->getScheme().'://'.$this->getPath();
    }
}