<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view\content;

use df;
use df\core;
use df\aura;

class ErrorContainer implements \ArrayAccess {
    
    protected $_exception;
    protected $_view;
    
    public static function factory(aura\view\IView $view, $error) {
        return new self($view, $error);
    }
    
    public function __construct(aura\view\IView $view, $exception) {
        $this->_view = $view;
        
        if(!$exception instanceof \Exception) {
            $exception = new \Exception((string)$exception);
        }
        
        $this->_exception = $exception;
    }
    
    public function __toString() {
        if(df\Launchpad::$application->isTesting()) {
            core\debug()->exception($this->_exception);
            $message = $this->_view->esc('Error: '.$this->_exception->getMessage());
            
            if($this->_view->getType() == 'Html') {
                $message = '<span class="state-error" title="'.$this->_view->esc($this->_exception->getFile().' : '.$this->_exception->getLine()).'">'.
                    $message.'</span>';
            }

            return $message;
            
        }
        
        return '';
    }
    
    public function __get($member) {
        return $this;    
    }
    
    public function __call($member, $args) {
        return $this;    
    }
    
    public function offsetGet($member) {
        return $this;
    }
    
    public function offsetSet($member, $value) {
        return $this;    
    }
    
    public function offsetUnset($member) {
        return $this;    
    }
    
    public function offsetExists($member) {
        return false;    
    }
}