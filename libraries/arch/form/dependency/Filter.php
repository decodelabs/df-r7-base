<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form\dependency;

use df;
use df\core;
use df\arch;
    
class Filter implements arch\form\IDependency, core\IDumpable {

    use arch\form\TDependency;

    protected $_value;

    public function __construct($context, $value, $name=null) {
        if($name === null) {
            $name = $context;
        }

        $this->_name = $name;
        $this->_value = $value;
        $this->setContext($context);
    }

    public function hasValue() {
        return true;
    }

    public function getValue() {
        return $this->_value;
    }

// Dump
    public function getDumpProperties() {
        $output = array();

        if($this->_name != $this->_context) {
            $output['name'] = $this->_name;
        }

        $output['value'] = $this->_value;
        $output['context'] = $this->_context;

        return $output;
    }
}