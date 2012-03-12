<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget\util;

use df;
use df\core;
use df\aura;

class Field implements aura\html\widget\IField, core\IDumpable {
    
    protected $_key;
    protected $_name;
    protected $_renderer;
    
    public function __construct($key, $name, Callable $renderer) {
        $this->_key = $key;
        $this->setName($name);
        $this->setRenderer($renderer);
    }
    
    
// Key
    public function getKey() {
        return $this->_key;
    }
    
// Name
    public function setName($name) {
        $this->_name = $name;
        return $this;
    }
    
    public function getName() {
        return $this->_name;
    }
    
// Renderer
    public function setRenderer(Callable $renderer) {
        $this->_renderer = $renderer;
        return $this;
    }
    
    public function getRenderer() {
        return $this->_renderer;
    }
    
    public function render($data, aura\view\IRenderTarget $renderTarget, RendererContext $renderContext) {
        $renderer = $this->_renderer;
        $value = $renderer($data, $renderTarget, $renderContext);
        
        if(empty($value) && $value != '0') {
            $value = new aura\html\ElementString('<span class="prop-na">n/a</span>');
        }
        
        return $value;
    }
    
    
// Dump
    public function getDumpProperties() {
        $output = [
            'key' => $this->_key
        ];
        
        $exp = false;
        
        if($this->_name != $this->_key) {
            $output['name'] = $this->_name;
            $exp = true;
        }
        
        if(!$this->_renderer instanceof \Closure) {
            $output['renderer'] = $this->_renderer;
            $exp = true;
        }
        
        if(!$exp) {
            return $this->_key;
        }
        
        return $output;
    }
}
