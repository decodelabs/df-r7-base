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

class Dn implements IDn, core\IDumpable {

    use core\TStringProvider;
    use core\collection\TArrayCollection;
    use core\collection\TArrayCollection_ProcessedIndexedValueMap;
    use core\collection\TArrayCollection_Seekable;
    use core\collection\TArrayCollection_Sliceable;
    use core\collection\TArrayCollection_ProcessedShiftable;
    use core\collection\TArrayCollection_IndexedMovable;
    use core\collection\TArrayCollection_Constructor;

    public static function factory() {
        if(func_num_args()) {
            $dn = func_get_arg(0);
        } else {
            $dn = null;
        }

        if($dn instanceof IDn) {
            return $dn;
        }

        if($dn === null || !strlen($dn)) {
            $dn = [];
        }

        if(is_string($dn)) {
            return self::fromString($dn);
        } else if(is_array($dn)) {
            return new self($dn);
        }

        throw new InvalidDnException('Invalid DN input');
    }

    public static function fromString($dn) {
        $key = null;
        $value = null;
        $length = strlen($dn);
        $state = 1;

        $keyIndex = $valueIndex = 0;

        $output = [];
        $rdn = new Rdn();
        $currentSetKeys = [];

        if($length && false === strpos($dn, '=')) {
            $parts = explode('.', $dn);
            $dn = [];

            foreach($parts as $part) {
                $dn[] = 'dc='.$part;
            }

            $dn = implode(',', $dn);
            $length = strlen($dn);
        }

        for($pos = 0; $pos <= $length; $pos++) {
            $char = $pos == $length ? null : $dn[$pos];

            switch($state) {
                case 1:
                    if($char === '=') {
                        $key = trim(substr($dn, $keyIndex, $pos - $keyIndex));

                        if(in_array(strtolower($key), $currentSetKeys)) {
                            throw new InvalidDnException(
                                'Duplicate multi key '.$key.' in DN'
                            );
                        }

                        $currentSetKeys[] = $key;

                        $valueIndex = $pos + 1;
                        $state = 2;
                    } else if($char === ',' || $char === ';' || $char === '+') {
                        throw new InvalidDnException(
                            'Unexpected '.$char.' character in DN'
                        );
                    }

                    break;

                case 2:
                    if($char === '\\') {
                        $state = 3;
                    } else if($char === ',' || $char === ';' || $char === '+' || $char === null) {
                        $value = self::unescapeValue(trim(substr($dn, $valueIndex, $pos - $valueIndex)));
                        $rdn->setAttribute($key, $value);

                        $state = 1;
                        $keyIndex = $pos + 1;

                        if($char !== '+') {
                            $output[] = $rdn;
                            $rdn = new Rdn();
                            $currentSetKeys = [];
                        }
                    } else if($char === '=') {
                        throw new InvalidDnException(
                            'Unexpected '.$char.' character in DN'
                        );
                    }

                    break;

                case 3:
                    $state = 2;
                    break;
            }
        }

        return new self($output);
    }

    public static function escapeValues(...$values) {
        foreach($values as $i => $value) {
            $values[$i] = self::escapeValue($value);
        }

        return $values;
    }

    public static function escapeValue($value) {
        $value = flex\Text::ascii32ToHex32(
            str_replace(
                ['\\', ',', '+', '"', '<', '>', ';', '#', '='],
                ['\\\\', '\,', '\+', '\"', '\<', '\>', '\;', '\#', '\='],
                $value
            )
        );

        if(preg_match('/^(\s*)(.+?)(\s*)$/', $value, $matches)) {
            $value = $matches[2];

            for($i = 0; $i < strlen($matches[1]); $i++) {
                $value = '\20'.$value;
            }

            for($i = 0; $i < strlen($matches[3]); $i++) {
                $value .= '\20';
            }
        }

        if($value === null) {
            $value = '\0';
        }

        return $value;
    }

    public static function unescapeValues(...$values) {
        foreach($values as $i => $value) {
            $values[$i] = self::unescapeValue($value);
        }

        return $values;
    }

    public static function unescapeValue($value) {
        return flex\Text::hex32ToAscii32(
            str_replace(
                ['\\\\', '\,', '\+', '\"', '\<', '\>', '\;', '\#', '\='],
                ['\\', ',', '+', '"', '<', '>', ';', '#', '='],
                $value
            )
        );
    }


    public function toString(): string {
        return $this->implode(',');
    }

    public function implode($separator=',', $case=flex\ICase::NONE) {
        $output = [];

        foreach($this->_collection as $rdn) {
            $output[] = $rdn->implode($case);
        }

        return implode($separator, $output);
    }

    public function isChildOf($dn) {
        try {
            $dn = self::factory($dn);
        } catch(\Throwable $e) {
            return false;
        }

        $startIndex = count($this->_collection) - ($targetCount = count($dn->_collection));

        if($startIndex < 0) {
            return false;
        }

        for($i = 0; $i < $targetCount; $i++) {
            if(!$this->_collection[$i + $startIndex]->eq($dn->_collection[$i])) {
                return false;
            }
        }

        return true;
    }

    public function getFirstEntry($key) {
        foreach($this->_collection as $rdn) {
            if(null !== ($val = $rdn->getAttribute($key))) {
                return $val;
            }
        }

        return null;
    }

    public function getAllEntries($key) {
        $output = [];

        foreach($this->_collection as $rdn) {
            if(null !== ($val = $rdn->getAttribute($key))) {
                $output[] = $val;
            }
        }

        return $output;
    }

    public function buildDomain() {
        return implode('.', $this->getAllEntries('dc'));
    }


    public function getReductiveIterator() {
        return new core\collection\ReductiveIndexIterator($this);
    }

    protected function _expandInput($value) {
        return [Rdn::factory($value)];
    }

    protected function _onInsert() {}


// Dump
    public function getDumpProperties() {
        return $this->implode();
    }
}