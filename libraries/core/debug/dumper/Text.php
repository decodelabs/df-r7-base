<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Text implements IStringNode {

    use core\TStringProvider;
    use TNode;

    protected $_string;

    public function __construct(IInspector $inspector, $string) {
        $this->_inspector = $inspector;
        $this->_string = $string;
    }

    public function getValue() {
        return $this->_string;
    }

    public function getDataValue() {
        return $this->_string;
    }

    public function toString(): string {
        return '"'.$this->_string.'"';
    }
}
