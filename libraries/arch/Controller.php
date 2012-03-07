<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df;
use df\core;
use df\arch;
use df\user;

class Controller implements IController {
    
    const CHECK_ACCESS = true;
    const DEFAULT_ACCESS = false;//user\Client::NONE;
    
    protected $_type;
    protected $_context;
    
    private $_isInline = false;
    
    public static function factory(IContext $context) {
        $request = $context->getRequest();
        $path = $request->getController();
        
        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = array();
        }
            
        $type = $context->getApplication()->getRunMode();
        $parts[] = $type.'Controller';
        
        $class = 'df\\apex\\directory\\'.$request->getArea().'\\'.implode('\\', $parts);
        
        if(!class_exists($class)) {
            $class = __CLASS__;
        }
        
        return new $class($context, $type);
    }
    
    protected function __construct(arch\IContext $context, $type) {
        $this->_context = $context;
        $this->_type = $type;
        $this->_isInline = get_class($this) == __CLASS__;
    }
    
    public function getContext() {
        return $this->_context;
    }
    
    public function getType() {
        return $this->_type;
    }
    
    
// Dispatch
    public function isControllerInline() {
        return $this->_isInline;
    }
    
// Context proxies
    public function __call($method, $args) {
        return call_user_func_array(array($this->_context, $method), $args);
    }
    
    public function __get($key) {
        return $this->_context->__get($key);
    }
    
    
// Access
    public function getAccessLockDomain() {
        return 'directory';
    }
    
    public function lookupAccessKey(array $keys) {
        return $this->_context->getRequest()->lookupAccessKey($keys);
    }
    
    public function getDefaultAccess() {
        return static::DEFAULT_ACCESS;
    }
}