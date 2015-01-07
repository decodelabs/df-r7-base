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
    protected $_errorContainer;
    protected $_errorPosition = 'top';
    protected $_isRequired = false;
    
    public function __construct(arch\IContext $context, $labelBody=null, $errorPosition=null) {
        parent::__construct($context);
        
        $this->_label = new Label($context, $labelBody);

        if($errorPosition !== null) {
            $this->setErrorPosition($errorPosition);
        }
    }
    
    public function setRenderTarget(aura\view\IRenderTarget $renderTarget=null) {
        $this->_label->setRenderTarget($renderTarget);
        return parent::setRenderTarget($renderTarget);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $view = $this->getRenderTarget()->getView();

        $this->_prepareChildren();
        
        $primaryWidget = $fieldError = null;
        $errors = [];
        $isRequired = $this->_isRequired;
        $errorPosition = $this->_errorPosition;
        $isStacked = $this->isStacked();

        if($isStacked) {
            $errorPosition = 'middle';
        }

        if($this->_errorContainer) {
            $errors = $this->_errorContainer->getErrors();
        }
        
        $this->_walkChildren($this->_children->toArray(), $errors, $isRequired, $primaryWidget);
        $output = [];

        if(!empty($errors)) {
            $tag->addClass('error');
            $fieldError = new FieldError($this->_context, $errors);
            $fieldError->setRenderTarget($this->getRenderTarget());

            if($errorPosition == 'top') {
                $output[] = $fieldError->render();
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

        if(!$isStacked || $this->_label->hasBody()) {
            $labelContainer = new aura\html\Element('div.widget-labelArea', $this->_label);
            $output[] = $labelContainer;
        }

        if($fieldError && $errorPosition == 'middle') {
            $output[] = $fieldError->render();
        }

        $inputAreaBody = $this->_children;

        if($this->_description !== null) {
            $inputAreaBody = [
                new aura\html\Element(
                    'p', 
                    [$view->html->icon('info'), ' ', $this->_description], 
                    ['class' => 'description info']
                ),
                $this->_children
            ];
        }

        $output[] = new aura\html\Element('div.widget-inputArea', $inputAreaBody);

        if($fieldError && $errorPosition == 'bottom') {
            $output[] = $fieldError->render();
        }
        
        if($isRequired) {
            $tag->addClass('required');
        }
        
        return $tag->renderWith($output, true);
    }

    protected function _walkChildren(array $children, &$errors, &$isRequired, &$primaryWidget) {
        foreach($children as $child) {
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
            } else if($child instanceof aura\html\IElement) {
                $this->_walkChildren($child->toArray(), $errors, $isRequired, $primaryWidget);
            }
        }
    }

    public function renderInputArea() {
        $primaryWidget = null;
        $errors = [];
        $isRequired = $this->_isRequired;

        if($this->_errorContainer) {
            $errors = $this->_errorContainer->getErrors();
        }
        
        foreach($this->_children as $child) {
            if($child instanceof IInputWidget) {
                $value = $child->getValue();
                
                if($value->hasErrors()) {
                    $errors = array_merge($errors, $value->getErrors());
                }
            }
        }
        
        $inputAreaBody = $this->_children;

        if($this->_description !== null) {
            $inputAreaBody = [
                new aura\html\Element(
                    'p', 
                    [$view->html->icon('info'), ' ', $this->_description], 
                    ['class' => 'description info']
                ),
                $this->_children
            ];
        }

        return (new aura\html\Element('div', $inputAreaBody, ['class' => 'widget-inputArea']))->render();
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

// Error
    public function setErrorContainer(core\IErrorContainer $errorContainer=null) {
        $this->_errorContainer = $errorContainer;
        return $this;
    }

    public function getErrorContainer() {
        return $this->_errorContainer;
    }

    public function setErrorPosition($position) {
        $position = strtolower($position);

        switch($position) {
            case 'top':
            case 'middle':
            case 'bottom':
                $this->_errorPosition = $position;
                break;

            default:
                $this->_errorPosition = 'top';
                break;
        }

        return $this;
    }

    public function getErrorPosition() {
        return $this->_errorPosition;
    }

// Required
    public function isRequired($flag=null) {
        if($flag !== null) {
            $this->_isRequired = (bool)$flag;
            return $this;
        }

        return $this->_isRequired;
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

// Stacked
    public function isStacked($flag=null) {
        $tag = $this->getTag();

        if($flag !== null) {
            if((bool)$flag) {
                $tag->addClass('stacked');
            } else {
                $tag->removeClass('stacked');
            }

            return $this;
        }

        return $tag->hasClass('stacked');
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
