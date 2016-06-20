<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Immutable implements IImmutableNode {

    use core\TStringProvider;
    use TNode;

    protected $_value;

    public function __construct(IInspector $inspector, $value) {
        $this->_inspector = $inspector;
        $this->_value = $value;
    }

    public function isNull() {
        return $this->_value === null;
    }

    public function isBoolean() {
        return $this->_value !== null;
    }

    public function getType() {
        if($this->_value === null) {
            return 'null';
        } else {
            return 'boolean';
        }
    }

    public function getValue() {
        return $this->_value;
    }

    public function getDataValue() {
        return $this->_value;
    }

    public function toString(): string {
        if($this->_value === true) {
            return 'true';
        } else if($this->_value === false) {
            return 'false';
        } else if($this->_value === null) {
            return 'null';
        } else {
            return '';
        }
    }
}
