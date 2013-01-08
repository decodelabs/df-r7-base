<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget\util;

use df;
use df\core;
use df\aura;

class RendererContext implements aura\html\widget\IRendererContext {
    
    use core\collection\TArrayCollection;
    use core\collection\TArrayCollection_AssociativeValueMap;

    protected $_key;
    protected $_field;
    protected $_counter = -1;
    protected $_cellTag;
    protected $_rowTag;
    protected $_store = array();
    protected $_widget;
    
    public function __construct(aura\html\widget\IWidget $widget) {
        $this->_widget = $widget;
    }
    
    public function getWidget() {
        return $this->_widget;
    }
    
    public function getKey() {
        return $this->_key;
    }

    public function getField() {
        return $this->_field;
    }
    
    public function getCounter() {
        return $this->_counter;
    }
    
    public function getCellTag() {
        return $this->_cellTag;
    }
    
    public function getRowTag() {
        return $this->_rowTag;
    }
    
    
    
    public function iterate($key, aura\html\ITag $cellTag=null, aura\html\ITag $rowTag=null) {
        $this->_counter++;
        $this->clear();

        $this->_store = array();
        $this->_key = $key;
        $this->_cellTag = $cellTag;
        $this->_rowTag = $rowTag;

        return $this;
    }
    
    public function iterateField($field, aura\html\ITag $cellTag=null, aura\html\ITag $rowTag=null) {
        $this->_field = $field;
        $this->_cellTag = $cellTag;
        $this->_rowTag = $rowTag;
        
        return $this;
    }


    public function setRenderTarget(aura\view\IRenderTarget $renderTarget=null) {
        $this->_widget->setRenderTarget($renderTarget);
        return $this;
    }

    public function getRenderTarget() {
        return $this->_widget->getRenderTarget();
    }

    public function getView() {
        return $this->_widget->getView();
    }


// Store
    public function setStore($key, $value) {
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
}
