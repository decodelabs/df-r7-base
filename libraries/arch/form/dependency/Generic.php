<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form\dependency;

use df;
use df\core;
use df\arch;
    
class Generic implements arch\form\IDependency {

    use arch\form\TDependency;

    protected $_value;

    public function __construct($name, $value, $error=null, $context=null) {
        $this->_name = $name;
        $this->_value = $value;
        $this->setErrorMessage($error);
        $this->setContext($context);
    }

    public function hasValue() {
        return !empty($this->_value);
    }

    public function getValue() {
        return $this->_value;
    }
}