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
    use core\collection\TArrayCollection_Constructor;
    use core\collection\TArrayCollection_AssociativeValueMap;

    public $cellTag;
    public $fieldTag;
    public $rowTag;
    public $key;
    public $field;
    public $counter = -1;

    protected $_store = [];
    protected $_widget;
    protected $_rowProcessor;
    protected $_nullToNa = true;
    protected $_skipRow = false;

    public function __construct(aura\html\widget\IWidget $widget) {
        $this->_widget = $widget;

        if($widget instanceof aura\html\widget\IMappedListWidget) {
            $this->_rowProcessor = $widget->getRowProcessor();
        }
    }

    public function getWidget() {
        return $this->_widget;
    }
    
    public function getKey() {
        return $this->key;
    }

    public function getField() {
        return $this->field;
    }
    
    public function getCounter() {
        return $this->counter;
    }
    
    public function getCellTag() {
        return $this->cellTag;
    }
    
    public function getRowTag() {
        return $this->rowTag;
    }

    public function getFieldTag() {
        return $this->fieldTag;
    }
    
    public function prepareRow($row) {
        if($this->_rowProcessor) {
            $c = $this->_rowProcessor;
            $row = $c($row);
        }

        return $row;
    }

    public function shouldConvertNullToNa($flag=null) {
        if($flag !== null) {
            $this->_nullToNa = (bool)$flag;
            return $this;
        }

        return $this->_nullToNa;
    }
    
    
    public function iterate($key, aura\html\ITag $cellTag=null, aura\html\ITag $rowTag=null, aura\html\ITag $fieldTag=null) {
        $this->counter++;
        $this->clear();

        $this->_store = [];
        $this->key = $key;
        $this->cellTag = $cellTag;
        $this->rowTag = $rowTag;
        $this->fieldTag = $fieldTag;
        $this->_skipRow = false;

        return $this;
    }
    
    public function iterateField($field, aura\html\ITag $cellTag=null, aura\html\ITag $rowTag=null, aura\html\ITag $fieldTag=null) {
        $this->field = $field;
        $this->cellTag = $cellTag;
        $this->rowTag = $rowTag;
        $this->fieldTag = $fieldTag;
        
        return $this;
    }

    public function renderCell($value, $renderer=null) {
        if($renderer) {
            try {
                $value = core\lang\Callback($renderer, $value, $this);
            } catch(\Exception $e) {
                if(!df\Launchpad::$isTesting) {
                    $value = new aura\html\ElementString('<span class="error">ERROR: '.$e->getMessage().'</span>');
                } else {
                    throw $e;
                }
            }
        }

        if($value instanceof core\IDescribable) {
            $value = $value->getOutputDescription();
        }

        if($value instanceof aura\html\IRenderable) {
            $value = $value->render();
        } else if($value instanceof aura\view\IRenderable) {
            $value = $value->renderTo($this->getView());
        }
        
        if($this->_nullToNa && empty($value) && $value != '0') {
            $value = new aura\html\ElementString('<span class="na">n/a</span>');
        }

        if($value instanceof core\time\IDate) {
            $value = $this->getView()->html->userDate($value);
        }

        return $value;
    }

    public function skipRow() {
        $this->_skipRow = true;
        return;
    }

    public function shouldSkipRow() {
        return $this->_skipRow;
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
