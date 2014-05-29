<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form\dependency;

use df;
use df\core;
use df\arch;
    
class Value implements arch\form\IDependency, core\IDumpable {

    use arch\form\TDependency;

    protected $_node;

    public function __construct($name, core\collection\IInputTree $node, $error=null, $context=null, $filter=false) {
        $this->_name = $name;
        $this->_node = $node;
        $this->setErrorMessage($error);
        $this->setContext($context);

        if($filter === false) {
            $this->_shouldFilter = false;
        } else if(is_callable($filter)) {
            $this->setCallback($filter);
        }
    }

    public function getValueNode() {
        return $this->_node;
    }

    public function hasValue() {
        return $this->_node->hasValue();
    }

    public function getValue() {
        return $this->_node->getValue();
    }

// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->_name,
            'node' => $this->_node,
            'errorMessage' => $this->_error,
            'context' => $this->_context,
            'callback' => $this->_callback
        ];
    }
}