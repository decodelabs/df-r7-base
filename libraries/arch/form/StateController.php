<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form;

use df;
use df\core;
use df\arch;

class StateController implements IStateController, \Serializable {
    
    protected $_sessionId;
    protected $_values;
    protected $_isNew = false;
    protected $_delegates = array();
    
    public function __construct($sessionId) {
        $this->_sessionId = $sessionId;
        $this->_values = new core\collection\InputTree();
        $this->_isNew = true;
    }
    
    public function serialize() {
        return serialize($this->_getSerializeValues());
    }
    
    protected function _getSerializeValues($withId=true) {
        $output = array();
        
        if($withId) {
            $output['id'] = $this->_sessionId;
        }
        
        if(!$this->_values->isEmpty()) {
            $output['vl'] = $this->_values;
        }
        
        if($this->_isNew) {
            $output['nw'] = true;
        }
        
        if(!empty($this->_delegates)) {
            $delegates = array();
            
            foreach($this->_delegates as $key => $delegate) {
                $delegates[$key] = $delegate->_getSerializeValues(false);
            }
            
            $output['dl'] = $delegates;
        }
        
        return $output;
    }
    
    public function unserialize($data) {
        if(is_array($values = unserialize($data))) {
            $this->_setUnserializedValues($values, $values['id']);
        }
        
        return $this;
    }
    
    protected function _setUnserializedValues(array $values, $id) {
        $this->_isNew = false;
        $this->_sessionId = $id;
        
        if(isset($values['vl'])) {
            $this->_values = $values['vl'];
        } else if(!$this->_values) {
            $this->_values = new core\collection\InputTree();
        }
        
        if(isset($values['nw'])) {
            $this->_isNew = true;
        }
        
        if(isset($values['dl'])) {
            foreach($values['dl'] as $key => $delegateData) {
                $delegate = new self($this->_sessionId);
                $delegate->_setUnserializedValues($delegateData, $id);
                
                $this->_delegates[$key] = $delegate;
            }
        }
    }
    
    public function getSessionId() {
        return $this->_sessionId;
    }
    
    public function getValues() {
        return $this->_values;
    }
    
    
    public function getDelegateState($id) {
        if(!isset($this->_delegates[$id])) {
            $this->_delegates[$id] = new self($this->_sessionId);
        }
        
        return $this->_delegates[$id];
    }
    
    
    public function isNew($flag=null) {
        if($flag !== null) {
            $this->_isNew = (bool)$flag;
            
            foreach($this->_delegates as $delegate) {
                $delegate->isNew($flag);
            }
            
            return $this;
        }
        
        return (bool)$this->_isNew;
    }
    
    public function reset() {
        $this->_values->clear();
        $this->_isNew = true;
        
        foreach($this->_delegates as $delegate) {
            $delegate->reset();
        }
        
        return $this;
    }
}
