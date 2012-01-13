<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class InputTree extends Tree implements IInputTree {
    
    use core\TErrorContainer;
    
    protected function _getSerializeValues() {
        $output = parent::_getSerializeValues();
        
        if(!empty($this->_errors)) {
            if($output === null) {
                $output = array();
            }
            
            $output['er'] = $this->_errors;
        }
        
        return $output;
    }
    
    protected function _setUnserializedValues(array $values) {
        parent::_setUnserializedValues($values);
        
        if(isset($values['er'])) {
            $this->_errors = $values['er'];
        }
    }
    
    public function importTree(ITree $child) {
        if($child instanceof IInputTree) {
            $this->_errors = $child->getErrors();
        }
        
        return parent::importTree($child);
    }
    
    public function merge(ITree $child) {
        if($child instanceof IInputTree) {
            $this->addErrors($child->getErrors());
        }
        
        return parent::importTree($child);
    }
    
    public function isValid() {
        if($this->hasErrors()) {
            return false;
        }
        
        foreach($this->_collection as $child) {
            if(!$child->isValid()) {
                return false;
            }
        }
        
        return true;
    }
    
// Dump
    public function getDumpProperties() {
        $children = array();
        
        foreach($this->_collection as $key => $child) {
            if($child instanceof self 
            && empty($child->_collection)
            && empty($child->_errors)) {
                $children[$key] = $child->_value;
            } else {
                $children[$key] = $child;
            }
        }
        
        $hasErrors = $this->hasErrors();
        
        if(empty($children) && !$hasErrors) {
            return $this->_value;
        }
        
        if($hasErrors) {
            array_unshift($children, new core\debug\dumper\Property('errors', $this->_errors, 'private'));
        }
        
        if(!empty($this->_value)) {
            array_unshift($children, new core\debug\dumper\Property(null, $this->_value, 'protected'));
        }
        
        return $children;
    }
}
