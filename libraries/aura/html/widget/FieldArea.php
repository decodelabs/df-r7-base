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

class FieldArea extends Container implements IFormOrientedWidget {
    
    protected $_label;
    protected $_description;
    
    public function __construct(arch\IContext $context, $labelBody=null) {
        parent::__construct($context);
        
        $this->_label = new Label($context, $labelBody);
    }
    
    public function setRenderTarget(aura\view\IRenderTarget $renderTarget=null) {
        $this->_label->setRenderTarget($renderTarget);
        return parent::setRenderTarget($renderTarget);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $view = $this->getRenderTarget()->getView();
        
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
        
        $output = [$this->_label->render()];
        $inputAreaBody = $this->_children;

        if($this->_description !== null) {
            $inputAreaBody = [
                new aura\html\Element(
                    'p', 
                    [$view->html->icon('info'), ' ', $this->_description], 
                    ['class' => 'description state-info']
                ),
                $this->_children
            ];
        }

        $output[] = new aura\html\Element('div', $inputAreaBody, ['class' => 'widget-inputArea']);
        
        if(!empty($errors)) {
            $tag->addClass('state-error');
            $fieldError = new FieldError($this->_context, $errors);
            $fieldError->setRenderTarget($this->getRenderTarget());
            //$output[] = $fieldError->render();
            array_unshift($output, $fieldError->render());
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


// Description
    public function setDescription($description) {
        $this->_description = $description;

        if(empty($this->_description)) {
            $this->_description = null;
        }

        return $this;
    }

    public function getDescription() {
        return $this->_description;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'label' => $this->_label,
            'description' => $this->_description,
            'children' => $this->_children,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
