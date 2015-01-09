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

abstract class Base implements IWidget {
    
    use aura\view\TDeferredRenderable;
    use core\string\THtmlStringEscapeHandler;
    
    use TWidget;
    
    const PRIMARY_TAG = 'div';
    
    private $_widgetName;
    
    public static function factory(arch\IContext $context, $name, array $args=[]) {
        $name = ucfirst($name);
        $class = 'df\\aura\\html\\widget\\'.$name;
        
        if(!class_exists($class)) {
            throw new WidgetNotFoundException(
                'Widget '.$name.' could not be found'
            );
        }

        array_unshift($args, $context);
        
        $reflection = new \ReflectionClass($class);
        $output = $reflection->newInstanceArgs($args);
        $output->_widgetName = $name;
        
        return $output;
    }
    
    public function __construct(arch\IContext $context) {}
    
    public function getWidgetName() {
        if(empty($this->_widgetName)) {
            $parts = explode('\\', get_class($this));
            $this->_widgetName = array_pop($parts);
        }
        
        return $this->_widgetName;
    }
    
    public function render() {
        if(!$this->_renderTarget) {
            throw new aura\view\RuntimeException(
                'No render target has been set'
            );
        }
        
        $output = $this->_render();
        
        if(!empty($output)) {
            return new aura\html\ElementString($output);
        }
    }
    
    public function __toString() {
        try {
            return (string)$this->render();
        } catch(\Exception $e) {
            core\debug()->exception($e);
            
            $renderTarget = $this->getRenderTarget();
            $message = $this->esc('Error rendering widget '.$this->getWidgetName());
            
            if($renderTarget) {
                if(df\Launchpad::$application->isTesting()) {
                    $message .= $this->esc(' - '.$e->getMessage()).'<br /><code>'.$this->esc($e->getFile().' : '.$e->getLine()).'</code>';
                }
            }
            
            return '<p class="error">'.$message.'</p>';
        }
    }
    
    abstract protected function _render();
    
        
    
// Id
    public function setId($id) {
        $this->getTag()->setId($id);
        return $this;
    }
    
    public function getId() {
        return $this->getTag()->getId();
    }

    public function isHidden($flag=null) {
        if($flag !== null) {
            $this->getTag()->isHidden($flag);
            return $this;
        }

        return $this->getTag()->isHidden();
    }

    public function setTitle($title) {
        $this->getTag()->setTitle($title);
        return $this;
    }

    public function getTitle() {
        return $this->getTag()->getTitle();
    }
    
    
    
// Attributes
    public function setAttributes(array $attributes) {
        $this->getTag()->setAttributes($attributes);
        return $this;
    }

    public function addAttributes(array $attributes) {
        $this->getTag()->addAttributes($attributes);
        return $this;
    }

    public function getAttributes() {
        return $this->getTag()->getAttributes();
    }

    public function setAttribute($attr, $value) {
        $this->getTag()->setAttribute($attr, $value);
        return $this;
    }
    
    public function getAttribute($attr, $default=null) {
        return $this->getTag()->getAttribute($attr, $default=null);
    }
    
    public function removeAttribute($attr) {
        $this->getTag()->removeAttribute($attr);
        return $this;
    }
    
    public function hasAttribute($attr) {
        return $this->getTag()->hasAttribute($attr);
    }

    public function countAttributes() {
        return $this->getTag()->countAttributes();
    }
    
    
    
// Data attributes
    public function addDataAttributes(array $attributes) {
        $this->getTag()->addDataAttributes($attributes);
        return $this;
    }

    public function setDataAttribute($key, $val) {
        $this->getTag()->setDataAttribute($key, $val);
        return $this;
    }
    
    public function getDataAttribute($key, $default=null) {
        return $this->getTag()->getDataAttribute($key, $default);
    }
    
    public function hasDataAttribute($key) {
        return $this->getTag()->hasDataAttribute();
    }
    
    public function removeDataAttribute($key) {
        $this->getTag()->removeDataAttribute($key);
        return $this;
    }
    
    public function getDataAttributes() {
        return $this->getTag()->getDataAttributes();
    }
    
    
    
// Classes
    public function setClasses($classes) {
        $this->getTag()->setClasses($classes);
        return $this;
    }
    
    public function addClasses($classes) {
        $this->getTag()->addClasses($classes);
        return $this;
    }
    
    public function getClasses() {
        return $this->getTag()->getClasses();
    }

    public function setClass($class) {
        $this->getTag()->setClass($class);
        return $this;
    }

    public function addClass($class) {
        $this->getTag()->addClass($class);
        return $this;
    }

    public function removeClass($class) {
        $this->getTag()->addClass($class);
        return $this;
    }

    public function hasClass($class) {
        return $this->getTag()->hasClass($class);
    }

    public function countClasses() {
        return $this->getTag()->countClasses();
    }
    
    
// Style
    public function setStyles($styles) {
        $this->getTag()->setStyles($styles);
        return $this;
    }
    
    public function addStyles($styles) {
        $this->getTag()->addStyles($styles);
        return $this;
    }
    
    public function getStyles() {
        return $this->getTag()->getStyles();
    }
    
    public function setStyle($key, $value) {
        $this->getTag()->setStyle($key, $value);
        return $this;
    }
    
    public function getStyle($key, $default=null) {
        return $this->getTag()->getStyle($key, $default);
    }
    
    public function removeStyle($key) {
        $this->getTag()->removeStyle($key);
        return $this;
    }
    
    public function hasStyle($key) {
        return $this->getTag()->hasStyle($key);
    }
}
