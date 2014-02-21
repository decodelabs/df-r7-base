<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form\dependency;

use df;
use df\core;
use df\arch;
    
class Selector implements arch\form\IDependency, core\IDumpable {

    use arch\form\TDependency;

    protected $_delegate;

    public function __construct(arch\form\ISelectorDelegate $delegate, $error=null, $context=null, Callable $callback=null) {
        $this->_name = $delegate->getDelegateKey();
        $this->_delegate = $delegate;
        $this->setErrorMessage($error);
        $this->setContext($context);
        $this->setCallback($callback);
        $this->_shouldFilter = false;
    }

    public function getDelegate() {
        return $this->_delegate;
    }

    public function hasValue() {
        return $this->_delegate->hasSelection();
    }

    public function getValue() {
        return $this->_delegate->getSelected();
    }

// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->_name,
            'delegate' => get_class($this->_delegate),
            'callback' => $this->_callback
        ];
    }
}