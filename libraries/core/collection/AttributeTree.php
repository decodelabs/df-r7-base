<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class AttributeTree extends Tree implements IAttributeContainer {
    
    use core\collection\TAttributeContainer;
    
    protected function _getSerializeValues() {
        $output = parent::_getSerializeValues();
        
        if(!empty($this->_attributes)) {
            if($output === null) {
                $output = [];
            }
            
            $output['at'] = $this->_attributes;
        }
        
        return $output;
    }
    
    protected function _setUnserializedValues(array $values) {
        parent::_setUnserializedValues($values);
        
        if(isset($values['at'])) {
            $this->_attributes = $values['at'];
        }
    }
    
    public function importTree(ITree $child) {
        if($child instanceof IAttributeTree) {
            $this->_attributes = $child->getAttributes();
        }
        
        return parent::importTree($child);
    }
    
    public function merge(ITree $child) {
        if($child instanceof IInputTree) {
            $this->_attributes = array_merge(
                $this->_attributes,
                $child->getAttributes()
            );
        }
        
        return parent::importTree($child);
    }
    
    
// Dump
    public function getDumpProperties() {
        $children = [];
        
        foreach($this->_collection as $key => $child) {
            if($child instanceof self 
            && empty($child->_collection)
            && empty($child->_attributes)) {
                $children[$key] = $child->_value;
            } else {
                $children[$key] = $child;
            }
        }
        
        $hasAttributes = !empty($this->_attributes);
        
        if(empty($children) && !$hasAttributes) {
            return $this->_value;
        }
        
        if($hasAttributes) {
            foreach(array_reverse($this->_attributes) as $key => $val) {
                array_unshift($children, new core\debug\dumper\Property(
                    $key, $val, 'private'
                ));
            }
        }
        
        if(!empty($this->_value)) {
            array_unshift($children, new core\debug\dumper\Property(null, $this->_value, 'protected'));
        }
        
        return $children;
    }
}
