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
    protected $_counter = -1;
    protected $_cellTag;
    protected $_rowTag;
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

        return $this->iterateField($key, $cellTag, $rowTag);
    }
    
    public function iterateField($key, aura\html\ITag $cellTag=null, aura\html\ITag $rowTag=null) {
        $this->_key = $key;
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
}
