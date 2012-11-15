<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form;

use df;
use df\core;
use df\arch;

trait TBase {
    
    public $view;
    public $html;
    public $content;
    public $values;
    
    protected $_state;
    protected $_delegates = array();
    
    protected function _init() {}
    protected function _setDefaultValues() {}
    protected function _setupDelegates() {}
    
    
    public function getStateController() {
        return $this->_state;
    }
    
// Delegates
    public function loadDelegate($id, $name, $request=null) {
        $context = $this->_context->spawnInstance($request);
        $request = $context->getRequest();
        $path = $request->getController();
        
        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = array();
        }
        
        $type = $context->getRunMode();

        $parts[] = '_formDelegates';
        $nameParts = explode('/', $name);
        $topName = array_pop($nameParts);

        if(!empty($nameParts)) {
            $parts[] += $nameParts;
        }

        $parts[] = ucfirst($topName);
        
        $class = 'df\\apex\\directory\\'.$request->getArea().'\\'.implode('\\', $parts);
        
        if(!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\'.implode('\\', $parts);

            if(!class_exists($class)) {
                throw new DelegateException(
                    'Delegate '.$name.' could not be found at ~'.$request->getArea().'/'.$request->getController()
                );
            }
        }
        
        return $this->_delegates[$id] = new $class(
            $context,
            $this->_state->getDelegateState($id),
            $this->_getDelegateIdPrefix().$id
        );
    }
    
    public function getDelegate($id) {
        if(!is_array($id)) {
            $id = explode('.', trim($id, ' .'));
        }
        
        if(empty($id)) {
            throw new DelegateException(
                'Empty delegate id detected'
            );
        }
        
        $top = array_shift($id);
        
        if(!isset($this->_delegates[$top])) {
            throw new DelegateException(
                'Delegate '.$top.' could not be found'
            );
        }
        
        $output = $this->_delegates[$top];
        
        if(!empty($id)) {
            $output = $output->getDelegate($id);
        }
        
        return $output;
    }
    
    
    protected function _getDelegateIdPrefix() {
        if($this instanceof IDelegate) {
            return $this->_delegateId.'.';
        }
        
        return '';
    }
    
    
    
// Values
    public function isValid() {
        if($this->_state && !$this->_state->getValues()->isValid()) {
            return false;
        }
        
        foreach($this->_delegates as $delegate) {
            if(!$delegate->isValid()) {
                return false;
            }
        }
        
        return true;
    }
    
    
// Names
    public function eventName($name) {
        $args = array_slice(func_get_args(), 1);
        $output = $this->_getDelegateIdPrefix().$name;
        
        if(!empty($args)) {
            foreach($args as $i => $arg) {
                $args[$i] = '\''.addslashes($arg).'\'';
            }
            
            $output .= '('.implode(',', $args).')';
        }
        
        return $output;
    }
    
    
    
// Events
    public function handleEvent($name, array $args=array()) {
        $func = '_on'.ucfirst($name).'Event';
        
        if(!method_exists($this, $func)) {
            $func = '_onDefaultEvent';
            
            if(!method_exists($this, $func)) {
                throw new EventException(
                    'Event '.$name.' does not have a handler'
                );
            }
        }
        
        return call_user_func_array(array($this, $func), $args);
    }

    protected function _onResetEvent() {
        $this->_state->reset();
    }
}