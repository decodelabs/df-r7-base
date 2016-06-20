<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap;

use df;
use df\core;
use df\opal;
use df\flex;

class Rdn implements IRdn, core\IDumpable {

    use core\collection\TAttributeContainer;
    use core\TStringProvider;

    public static function factory($rdn) {
        if($rdn instanceof IRdn) {
            return $rdn;
        }

        $parts = explode('+', (string)$rdn);
        $output = new self();

        foreach($parts as $part) {
            $values = explode('=', $part, 2);
            $output->setAttribute(array_shift($values), array_shift($values));
        }

        return $output;
    }

    public function __construct(array $attributes=[]) {
        $this->setAttributes($attributes);
    }

    public function setAttribute($key, $value) {
        $key = trim($key);
        $value = trim($value);

        if(is_numeric($key)) {
            throw new InvalidDnException(
                'Malformed rdn key: '.$key
            );
        }

        if(isset($this->_attributes[$key])) {
            throw new InvalidDnException(
                'Duplicate multi key '.$key.' in dn'
            );
        }

        $this->_attributes[$key] = $value;
        return $this;
    }

    public function getAttribute($key, $default=null) {
        $key = strtolower(trim($key));
        $attributes = $this->_attributes;
        array_change_key_case($attributes, \CASE_LOWER);

        if(isset($attributes[$key])) {
            return $attributes[$key];
        }

        return $default;
    }

    public function toString(): string {
        return $this->implode();
    }

    public function implode($case=flex\ICase::NONE) {
        $output = [];

        foreach($this->_attributes as $key => $value) {
            switch($case) {
                case flex\ICase::UPPER:
                    $key = strtoupper($key);
                    break;

                case flex\ICase::LOWER:
                    $key = strtolower($key);
                    break;
            }

            $value = Dn::escapeValue($value);
            $output[strtolower($key)] = $key.'='.$value;
        }

        ksort($output, \SORT_STRING);
        return implode('+', $output);
    }

    public function eq($rdn) {
        return $this->implode(flex\ICase::LOWER) == self::factory($rdn)->implode(flex\ICase::LOWER);
    }

    public function count() {
        return count($this->_attributes);
    }

// Dump
    public function getDumpProperties() {
        return $this->_attributes;
    }
}