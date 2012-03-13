<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class FieldArea extends Container implements IFormOrientedWidget {
    
    protected $_label;
    
    public function __construct($labelBody=null) {
        parent::__construct();
        
        $this->_label = new Label($labelBody);
    }
    
    public function setRenderTarget(aura\view\IRenderTarget $renderTarget=null) {
        $this->_label->setRenderTarget($renderTarget);
        return parent::setRenderTarget($renderTarget);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        
        $primaryWidget = null;
        $errors = array();
        $isRequired = false;
        
        foreach($this->_children as $child) {
            if($child instanceof IInputWidget) {
                if(!$primaryWidget) {
                    $primaryWidget = $child;
                }
                
                if(!$isRequired) {
                    $isRequired = $child->isRequired();
                }
                
                $value = $child->getValue();
                
                if($value->hasErrors()) {
                    $errors = array_merge($errors, $value->getErrors());
                }
            }
        }
        
        if($primaryWidget instanceof IFocusableInputWidget) {
            $inputId = $primaryWidget->getId();
            
            if($inputId === null) {
                $inputId = 'formInput-'.md5(uniqid('formInput-', true));
                $primaryWidget->setId($inputId);
            }
            
            $this->_label->setInputId($inputId);
        }
        
        $output = $this->_label->render();
        $output->append(' <div class="widget-inputArea">'.$this->_children.'</div>');
        
        if(!empty($errors)) {
            $tag->addClass('state-error');
            $fieldError = new FieldError($errors);
            $fieldError->setRenderTarget($this->getRenderTarget());
            $output->append($fieldError->render());
        }
        
        if($isRequired) {
            $tag->addClass('constraint-required');
        }
        
        return $tag->renderWith($output, true);
    }
    
    
// Label body
    public function withLabelBody() {
        return new aura\html\widget\util\ElementContentWrapper($this, $this->_label->getBody());
    }
    
    public function setLabelBody(aura\html\IElementContent $labelBody) {
        $this->_label->setBody();
        return $this;
    }
    
    public function getLabelBody() {
        return $this->_label->getBody();
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'label' => $this->_label,
            'children' => $this->_children,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
