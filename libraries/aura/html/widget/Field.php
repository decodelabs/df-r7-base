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

class Field extends Container implements IFormOrientedWidget {

    use core\constraint\TRequirable;

    protected $_label;
    protected $_description;
    protected $_errorContainer;

    public function __construct(arch\IContext $context, $labelBody=null, $input=null) {
        parent::__construct($context, $input);
        $this->_label = new Label($context, $labelBody);
    }

    protected function _render() {
        $tag = $this->getTag();

        $children = $this->_prepareChildren(function($child) {
            if($child instanceof arch\node\IInlineFieldRenderableDelegate) {
                return $child->renderFieldContent($this);
            }

            return $child;
        });

        $primaryWidget = $fieldError = null;
        $errors = [];
        $isRequired = $this->_isRequired;

        if($this->_errorContainer) {
            $errors = $this->_errorContainer->getErrors();
        }

        $this->_walkChildren($this->_children->toArray(), $errors, $isRequired, $primaryWidget);
        $output = [];

        if(!empty($errors)) {
            $tag->addClass('error');
            $fieldError = new FieldError($this->_context, $errors);
        }

        if($primaryWidget instanceof IFocusableInputWidget) {
            $inputId = $primaryWidget->getId();

            if($inputId === null) {
                $inputId = 'formInput-'.md5(uniqid('formInput-', true));
                $primaryWidget->setId($inputId);
            }

            $this->_label->setInputId($inputId);
        }

        $labelContainer = new aura\html\Element('div.w-labelArea', $this->_label);
        $output[] = $labelContainer;

        if(!$this->_label->hasBody()) {
            $labelContainer->addClass('empty');
        }


        if($fieldError) {
            $output[] = $fieldError->render();
        }

        $inputAreaBody = $children;

        if($this->_description !== null) {
            $inputAreaBody = [
                new aura\html\Element(
                    'p',
                    [$this->_context->html->icon('info'), ' ', $this->_description],
                    ['class' => 'description info']
                ),
                $children
            ];
        }

        $output[] = new aura\html\Element('div.w-inputArea', $inputAreaBody);

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

        $children = $this->_prepareChildren(function($child) {
            if($child instanceof arch\node\IInlineFieldRenderableDelegate) {
                $child = $child->renderFieldContent($this);
            }

            return $child;
        });

        if($this->_errorContainer) {
            $errors = $this->_errorContainer->getErrors();
        }

        foreach($children as $child) {
            if($child instanceof IInputWidget) {
                $value = $child->getValue();

                if($value->hasErrors()) {
                    $errors = array_merge($errors, $value->getErrors());
                }
            }
        }

        $inputAreaBody = $children;

        if($this->_description !== null) {
            $inputAreaBody = [
                new aura\html\Element(
                    'p',
                    [$this->_context->html->icon('info'), ' ', $this->_description],
                    ['class' => 'description info']
                ),
                $children
            ];
        }

        return (new aura\html\Element('div', $inputAreaBody, ['class' => 'w-inputArea']))->render();
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
    public function setErrorContainer(core\collection\IErrorContainer $errorContainer=null) {
        $this->_errorContainer = $errorContainer;
        return $this;
    }

    public function getErrorContainer() {
        return $this->_errorContainer;
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
            'tag' => $this->getTag()
        ];
    }
}
