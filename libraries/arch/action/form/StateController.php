<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\action\form;

use df;
use df\core;
use df\arch;

class StateController implements arch\action\IFormStateController, \Serializable {

    public $sessionId;
    public $values;
    public $isOperating = false;
    public $referrer;

    protected $_isNew = false;
    protected $_delegates = [];
    protected $_store = [];

    public function __construct($sessionId) {
        $this->sessionId = $sessionId;
        $this->values = new core\collection\InputTree();
        $this->_isNew = true;
    }

    public function serialize() {
        return serialize($this->_getSerializeValues());
    }

    protected function _getSerializeValues($withId=true) {
        $output = [];

        if($withId) {
            $output['id'] = $this->sessionId;
        }

        if(!$this->values->isEmpty()) {
            $output['vl'] = $this->values;
        }

        if($this->_isNew) {
            $output['nw'] = true;
        }

        if($this->referrer) {
            $output['rf'] = $this->referrer;
        }

        if(!empty($this->_delegates)) {
            $delegates = [];

            foreach($this->_delegates as $key => $delegate) {
                $delegates[$key] = $delegate->_getSerializeValues(false);
            }

            $output['dl'] = $delegates;
        }

        if(!empty($this->_store)) {
            $output['st'] = $this->_store;
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
        $this->sessionId = $id;

        if(isset($values['vl'])) {
            $this->values = $values['vl'];
        } else if(!$this->values) {
            $this->values = new core\collection\InputTree();
        }

        if(isset($values['nw'])) {
            $this->_isNew = true;
        }

        if(isset($values['rf'])) {
            $this->referrer = $values['rf'];
        }

        if(isset($values['dl'])) {
            foreach($values['dl'] as $key => $delegateData) {
                $delegate = new self($this->sessionId);
                $delegate->_setUnserializedValues($delegateData, $id);

                $this->_delegates[$key] = $delegate;
            }
        }

        if(isset($values['st']) && is_array($values['st'])) {
            $this->_store = $values['st'];
        }
    }

    public function getSessionId() {
        return $this->sessionId;
    }

    public function getValues() {
        return $this->values;
    }


    public function getDelegateState($id) {
        if(!isset($this->_delegates[$id])) {
            $this->_delegates[$id] = new self($this->sessionId);
        }

        return $this->_delegates[$id];
    }

    public function clearDelegateState($id) {
        if(isset($this->_delegates[$id])) {
            $this->_delegates[$id]->reset();
            unset($this->_delegates[$id]);
        }

        return $this;
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
        $this->values->clear();
        $this->_isNew = true;

        foreach($this->_delegates as $delegate) {
            $delegate->reset();
        }

        $this->clearStore();

        return $this;
    }

    public function isOperating() {
        if($this->isOperating) {
            return true;
        }

        foreach($this->_delegates as $child) {
            if($child->isOperating()) {
                return true;
            }
        }

        return false;
    }


// Store
    public function setStore($key, $value) {
        $this->isOperating = true;
        $this->_store[$key] = $value;
        return $this;
    }

    public function hasStore($key) {
        return isset($this->_store[$key]);
    }

    public function getStore($key, $default=null) {
        if(isset($this->_store[$key])) {
            return $this->_store[$key];
        }

        return $default;
    }

    public function removeStore($key) {
        unset($this->_store[$key]);
        return $this;
    }

    public function clearStore() {
        $this->_store = [];
        return $this;
    }
}
