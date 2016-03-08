<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Number implements INumberNode {

    use core\TStringProvider;
    use TNode;

    protected $_number;

    public function __construct(IInspector $inspector, $number) {
        $this->_inspector = $inspector;
        $this->_number = $number;
    }

    public function getValue() {
        return $this->_number;
    }

    public function isFloat() {
        return is_float($this->_number);
    }

    public function getDataValue() {
        return $this->_number;
    }

    public function toString() {
        $output = (string)$this->_number;

        if(is_float($this->_number)) {
            $output .= 'f';
        }

        return $output;
    }
}
