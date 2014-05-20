<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\component\template;

use df;
use df\core;
use df\arch;
use df\aura;
    
class AttributeList extends arch\component\Base implements aura\html\widget\IWidgetProxy {

    protected $_record;
    protected $_renderIfEmpty = null;
    protected $_fields = [];
    protected $_viewArg;

    protected function _init(array $fields=null, $record=null) {
        if($record) {
            $this->setRecord($record);
        }

        if(!empty($fields)) {
            $this->setFields($fields);
        }

        if($this->_viewArg === null) {
            $this->_viewArg = 'record';
        }
    }

// Record
    public function setRecord($record) {
        $this->_record = $record;
        return $this;
    }

    public function getRecord() {
        return $this->_record;
    }

// Error
    public function shouldRenderIfEmpty($flag=null) {
        if($flag !== null) {
            $this->_renderIfEmpty = (bool)$flag;
            return $this;
        }

        return $this->_renderIfEmpty;
    }

// Fields
    public function setFields(array $fields) {
        foreach($fields as $key => $value) {
            $this->setField($key, $value);
        }

        return $this;
    }

    public function setField($key, $value) {
        if($value === true && isset($this->_fields[$key]) && is_callable($this->_fields[$key])) {
            return $this;
        }

        if(!is_callable($value)) {
            $value = (bool)$value;
        }

        $this->_fields[$key] = $value;
        return $this;
    }

    public function getFields() {
        return $this->_fields;
    }

    public function hide($keys) {
        if(!is_array($keys)) {
            $keys = func_get_args();
        }

        foreach($keys as $key) {
            if(isset($this->_fields[$key])) {
                $this->_fields[$key] = false;
            }
        }

        return $this;
    }

    public function showField($keys) {
        if(!is_array($keys)) {
            $keys = func_get_args();
        }

        foreach($keys as $key) {
            if(isset($this->_fields[$key]) && $this->_fields[$key] == false) {
                $this->_fields[$key] = true;
            }
        }

        return $this;
    }

    public function isFieldVisible($key) {
        return isset($this->_fields[$key]) 
            && $this->_fields[$key] !== false;
    }

    public function addCustomField($key, Callable $callback) {
        $this->_fields[$key] = $callback;
        return $this;
    }

// View arg
    public function setViewArg($arg) {
        $this->_viewArg = $arg;
        return $this;
    }

    public function getViewArg() {
        return $this->_viewArg;
    }


// Render
    public function toWidget() {
        return $this->render();
    }

    protected function _execute() {
        if($this->_record === null
        && $this->_viewArg !== null
        && $this->view->hasArg($this->_viewArg)) {
            $this->_record = $this->view->getArg($this->_viewArg);
        }

        $output = $this->view->html->attributeList($this->_record);

        if($this->_renderIfEmpty !== null) {
            $output->shouldRenderIfEmpty($this->_renderIfEmpty);
        }

        foreach($this->_fields as $key => $value) {
            if($value === true) {
                $func = 'add'.ucfirst($key).'Field';

                if(method_exists($this, $func)) {
                    $this->{$func}($output);
                } else {
                    $output->addField($key);
                }
            } else if(is_callable($value)) {
                $value($output, $key);
            }
        }

        return $output;
    }
}