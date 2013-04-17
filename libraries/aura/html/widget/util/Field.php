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
    
    public $key;
    public $name;
    public $labels = array();
    public $renderer;
    
    public function __construct($key, $name, Callable $renderer=null) {
        $this->key = $key;
        $this->setName($name);
        $this->setRenderer($renderer);
    }
    
    
// Key
    public function getKey() {
        return $this->key;
    }
    
// Name
    public function setName($name) {
        $this->name = $name;
        return $this;
    }
    
    public function getName() {
        return $this->name;
    }


// Labels
    public function addLabel($key, $label=null) {
        if(empty($label)) {
            $label = core\string\Manipulator::formatLabel($key);
        }

        $this->labels[$key] = $label;
        return $this;
    }

    public function removeLabel($key) {
        unset($this->labels[$key]);
        return $this;
    }

    public function getLabels() {
        return $this->labels;
    }

    public function getHeaderList() {
        return array_merge([$this->key => $this->name], $this->labels);
    }
    
// Renderer
    public function setRenderer(Callable $renderer=null) {
        $this->renderer = $renderer;
        return $this;
    }
    
    public function getRenderer() {
        return $this->renderer;
    }
    
    public function render($data, aura\html\widget\IRendererContext $renderContext) {
        return $renderContext->renderCell($data, $this->renderer);
    }
    
    
// Dump
    public function getDumpProperties() {
        $output = [
            'key' => $this->key
        ];
        
        $exp = false;
        
        if($this->name != $this->key) {
            $output['name'] = $this->name;
            $exp = true;
        }
        
        if(!$this->renderer instanceof \Closure) {
            $output['renderer'] = $this->renderer;
            $exp = true;
        }
        
        if(!$exp) {
            return $this->key;
        }
        
        return $output;
    }
}
