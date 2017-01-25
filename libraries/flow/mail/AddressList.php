<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail;

use df;
use df\core;
use df\flow;

class AddressList implements IAddressList, \IteratorAggregate, core\IDumpable {

    use core\TValueMap;
    use core\collection\TExtractList;
    use core\collection\TExtricable;
    use core\TStringProvider;

    protected $_addresses = [];

    public static function factory($list) {
        if($list instanceof IAddressList) {
            return $list;
        }

        return new self($list);
    }

    public function __construct(...$input) {
        $this->import(...$input);
    }

    public function getIterator() {
        return new \ArrayIterator($this->_addresses);
    }

    public function import(...$args) {
        foreach(core\collection\Util::leaves($args) as $input) {
            if(is_string($input)) {
                $parts = explode(',', $input);
                $prefix = null;

                foreach($parts as $part) {
                    if(false === strpos($part, '@')) {
                        if($prefix) {
                            $prefix .= ',';
                        }

                        $prefix .= $part;
                        continue;
                    }

                    if($prefix) {
                        $part = $prefix.','.$part;
                    }

                    $this->add($part);
                    $prefix = null;
                }
            } else {
                $this->add($input);
            }
        }

        return $this;
    }

    public function isEmpty() {
        return empty($this->_addresses);
    }

    public function extract() {
        return array_shift($this->_addresses);
    }

    public function clear() {
        $this->_addresses = [];
        return $this;
    }

    public function count() {
        return count($this->_addresses);
    }

    public function toArray(): array {
        return $this->_addresses;
    }

    public function toNameMap() {
        $output = [];

        foreach($this->_addresses as $address) {
            $output[$address->getAddress()] = $address->getName();
        }

        return $output;
    }

    public function add($address, $name=null) {
        return $this->set($address, $name);
    }

    public function set($address, $name=null) {
        if(!$address = Address::factory($address, $name)) {
            return $this;
        }

        if(!$address->getName() && isset($this->_addresses[$address->getAddress()])) {
            $address->setName($this->_addresses[$address->getAddress()]->getName());
        }

        $this->_addresses[$address->getAddress()] = $address;
        return $this;
    }

    public function get($address, $default=null) {
        if($address instanceof IAddress) {
            $address = $address->getAddress();
        }

        $address = strtolower($address);

        if(isset($this->_addresses[$address])) {
            return $this->_addresses[$address];
        } else if($default !== null) {
            return Address::factory($default);
        }
    }

    public function has(...$addresses) {
        foreach($addresses as $address) {
            if($address instanceof IAddress) {
                $address = $address->getAddress();
            }

            $address = strtolower($address);

            if(isset($this->_addresses[$address])) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$addresses) {
        foreach($addresses as $address) {
            if($address instanceof IAddress) {
                $address = $address->getAddress();
            }

            $address = strtolower($address);
            unset($this->_addresses[$address]);
        }

        return $this;
    }



    public function toString(): string {
        return implode(', ', $this->_addresses);
    }


    public function offsetSet($key, $value) {
        return $this->set($key, $value);
    }

    public function offsetGet($key) {
        return $this->get($key);
    }

    public function offsetExists($key) {
        return $this->has($key);
    }

    public function offsetUnset($key) {
        return $this->remove($key);
    }


// Dump
    public function getDumpProperties() {
        return array_values($this->_addresses);
    }
}