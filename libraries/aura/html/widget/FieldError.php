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

class FieldError extends Base implements IFormOrientedWidget, core\IErrorContainer, core\IDumpable {
    
    protected $_errors = [];
    
    public function __construct(arch\IContext $context, $errors=null) {
        if($errors !== null) {
            if($errors instanceof core\IErrorContainer) {
                $errors = $errors->getErrors();
            }
            
            $this->addErrors($errors);
        }
    }
    
    protected function _render() {
        if(empty($this->_errors)) {
            return '';
        }

        $tag = $this->getTag();
        $output = new aura\html\ElementContent();
        
        foreach($this->_errors as $code => $error) {
            $output->push(
                new aura\html\Element(
                    'div', $error,
                    ['data-errorid' => $code]
                )
            );
        }
        
        return $tag->renderWith($output, true);
    }
    
    public function isValid() {
        if($this->hasErrors()) {
            return false;
        }
        
        return true;
    }
    
    public function setErrors(array $errors) {
        $this->_errors = [];
        return $this->addErrors($errors);
    }
    
    public function addErrors(array $errors) {
        foreach($errors as $code => $message) {
            $this->addError($code, $message);
        }    
        
        return $this;
    }
    
    public function addError($code, $message) {
        $this->_errors[$code] = $message;
        return $this;
    }
    
    public function getErrors() {
        return $this->_errors;
    }
    
    public function getError($code) {
        if(isset($this->_errors[$code])) {
            return $this->_errors[$code];
        }
        
        return null;
    }
    
    public function hasErrors() {
        return !empty($this->_errors);
    }
    
    public function hasError($code) {
        return isset($this->_errors[$code]);
    }
    
    public function clearErrors() {
        $this->_errors = [];
        return $this;
    }
    
    public function clearError($code) {
        unset($this->_errors[$code]);
        return $this;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'errors' => $this->_errors,
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
