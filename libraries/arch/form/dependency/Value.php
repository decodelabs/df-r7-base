<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form\dependency;

use df;
use df\core;
use df\arch;
    
class Value implements arch\form\IDependency {

    use arch\form\TDependency;

    protected $_node;

    public function __construct($name, core\collection\IInputTree $node, $error=null, $context=null) {
        $this->_name = $name;
        $this->_node = $node;
        $this->setErrorMessage($error);
        $this->setContext($context);
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
}