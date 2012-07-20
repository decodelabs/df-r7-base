<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view\content;

use df;
use df\core;
use df\aura;
use df\arch;
    
class GenericRenderer implements aura\view\IDeferredRenderable {

	use aura\view\TDeferredRenderable;

	protected $_value;

	public function __construct($value) {
		$this->_value = $value;
	}

	public function getValue() {
		return $this->_value;
	}

	public function render() {
		if($this->_value instanceof aura\view\IDeferredRenderable) {
			return $this->_value->renderTo($this->getRenderTarget());
		} else if($this->_value instanceof aura\view\IRenderable
		|| $this->_value instanceof aura\html\IRenderable) {
			return $this->_value->render();
		}

		return (string)$this->_value;
	}

	public function toString() {
		return (string)$this->render();
	}

	public function __toString() {
        try {
            return (string)$this->render();
        } catch(\Exception $e) {
            core\debug()->exception($e);
            
            $renderTarget = $this->getRenderTarget();
            $message = $this->esc('Error rendering widget '.$this->getWidgetName());
            
            if($renderTarget) {
                $application = $renderTarget->getView()->getContext()->getApplication();
            
                if($application->isDevelopment()) {
                    $message .= $this->esc(' - '.$e->getMessage()).'<br /><code>'.$this->esc($e->getFile().' : '.$e->getLine()).'</code>';
                }
            }
            
            return '<p class="error">'.$message.'</p>';
        }
    }
}