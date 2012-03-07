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

abstract class Component implements IComponent {
    
    const DEFAULT_ACCESS = true;//user\Client::ALL;
    
    protected $_context;
    
    public static function factory(IContext $context, $name) {
        $request = $context->getRequest();
        $path = $request->getController();
        
        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = array();
        }
        
        $type = $context->getRunMode();
        
        $parts[] = '_components';
        $parts[] = ucfirst($name);
        
        $class = 'df\\apex\\directory\\'.$request->getArea().'\\'.implode('\\', $parts);
        
        if(!class_exists($class)) {
            throw new RuntimeException(
                'Component ~'.$request->getArea().'/'.$path.'/'.ucfirst($name).' could not be found'
            );
        }
        
        return new $class($context);
    }
    
    public function __construct(arch\IContext $context) {
        $this->_context = $context;
    }
    
    public function getContext() {
        return $this->_context;
    }
    
    public function getName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
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
