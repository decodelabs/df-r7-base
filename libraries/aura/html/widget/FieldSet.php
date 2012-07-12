<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;

class FieldSet extends Container implements IFieldSetWidget, IWidgetShortcutProvider {
    
    use TWidget_Disableable;
    
    const PRIMARY_TAG = 'fieldset';
    
    protected $_legendBody;
    protected $_name;
    protected $_targetFormId;
    
    public function __construct(arch\IContext $context, $legend=null) {
        parent::__construct($context);
        
        if($legend instanceof aura\html\IElementContent) {
            $this->setLegendBody($legend);
        } else {
            $this->_legendBody = new aura\html\ElementContent($legend);
        }
    }
    
    protected function _render() {
        $tag = $this->getTag();
        
        if($this->_name !== null) {
            $tag->setAttribute('name', $this->_name);
        }
        
        if($this->_targetFormId !== null) {
            $tag->setAttribute('form', $this->_targetFormId);
        }
        
        if($this->_isDisabled) {
            $tag->setAttribute('disabled', 'disabled');
        }
        
        $legend = null;
        
        if(!$this->_legendBody->isEmpty()) {
            $legend = new aura\html\Element('legend', $this->_legendBody);
        }
        
        return $tag->renderWith(array($legend->render(), $this->_children), true);
    }
    
    
// Legend
    public function withLegendBody() {
        return new aura\html\widget\util\ElementContentWrapper($this, $this->_legendBody);
    }
    
    public function setLegendBody(aura\html\IElementContent $body) {
        $this->_legendBody = $body;
        return $this;
    }
    
    public function getLegendBody() {
        return $this->_legendBody;
    }
    
    
// Name
    public function setName($name) {
        $this->_name = $name;
        return $this;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    
// Target form
    public function setTargetFormId($id) {
        $this->_targetFormId = $id;
        return $this;
    }
    
    public function getTargetFormId() {
        return $this->_targetFormId;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->_name,
            'form' => $this->_targetFormId,
            'legend' => $this->_legendBody,
            'children' => $this->_children,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
