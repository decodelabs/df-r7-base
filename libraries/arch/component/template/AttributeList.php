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
        if(is_string($value) && is_int($key)) {
            $key = $value;
            $value = true;
        }

        if($key == '--') {
            $key = uniqid('--');
            $value = '';
        }

        if($value === true && isset($this->_fields[$key]) && $this->_fields[$key] instanceof core\lang\ICallback) {
            return $this;
        }

        if(is_callable($value)) {
            $value = core\lang\Callback::factory($value);
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

    public function addCustomField($key, $callback) {
        $this->_fields[$key] = core\lang\Callback::factory($callback);
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
        && $this->view->hasSlot($this->_viewArg)) {
            $this->_record = $this->view->getSlot($this->_viewArg);
        }

        $output = [];
        $list = $this->_createBaseList();
        $divider = 0;

        foreach($this->_fields as $key => $value) {
            if(substr($key, 0, 2) == '--') {
                if(is_string($value)) {
                    $title = $value;
                } else if($key != '--') {
                    $title = $this->view->format->name(substr($key, 2));
                } else {
                    $title = null;
                }

                $list->addField('divider'.($divider++), function($data, $context) use($title) {
                    if($title) {
                        $context->setDivider($title);
                    } else {
                        $context->addDivider();
                    }

                    $context->skipRow();
                });

                /*
                if($list->hasFields()) {
                    $output[] = $list;
                }

                if(strlen($title)) {
                    $output[] = $this->view->html('h4', $title);
                }

                $list = $this->_createBaseList();
                */
                continue;
            }

            if($value === true) {
                $func = 'add'.ucfirst($key).'Field';

                if(method_exists($this, $func)) {
                    $this->{$func}($list);
                } else {
                    $list->addField($key);
                }
            } else if(is_callable($value)) {
                core\lang\Callback($value, $list, $key);
            }
        }

        if($list->hasFields()) {
            $output[] = $list;
        }

        if(count($output) < 2) {
            $output = array_pop($output);
        } else {
            $output = $this->view->html('div', $output);
        }

        return $output;
    }

    protected function _createBaseList() {
        $output = $this->view->html->attributeList($this->_record);
        $output->getRendererContext()->setComponent($this);

        if($this->_renderIfEmpty !== null) {
            $output->shouldRenderIfEmpty($this->_renderIfEmpty);
        }

        return $output;
    }
}