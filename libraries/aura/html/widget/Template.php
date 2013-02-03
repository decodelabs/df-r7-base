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

class Template extends Base implements ITemplateWidget, \ArrayAccess, core\IDumpable {
    
    protected $_path;
    protected $_location;
    protected $_args = array();
    
    public function __construct(arch\IContext $context, $path, $location=null) {
        $this->setPath($path);
        $this->setLocation($location);
    }

    public function toResponse() {
        return $this->_loadTemplate();
    }
    
    protected function _loadTemplate() {
        $renderTarget = $this->getRenderTarget();
        $view = $renderTarget->getView();
        $context = $view->getContext()->spawnInstance($this->_location);
        return aura\view\content\Template::loadDirectoryTemplate($context, $this->_path);
    }

    protected function _render() {
        $tag = $this->getTag();
        $template = $this->_loadTemplate();

        if(!empty($this->_args)) {
            $template->setArgs($this->_args);
        }

        $output = new aura\html\ElementString(
            $template->renderTo($this->getRenderTarget())
        );

        if($tag->countAttributes() <= 1 && $tag->countClasses() == 1) {
            return $output;
        }
        
        return $tag->renderWith($output);
    }
    
    
// Path
    public function setPath($path) {
        $this->_path = $path;
        return $this;
    }
    
    public function getPath() {
        return $this->_path;
    }
    
// Context request
    public function setLocation($location) {
        $this->_location = $location;
        return $this;
    }
    
    public function getLocation() {
        return $this->_location;
    }    
    
    
// Args
    public function setArg($name, $value) {
        $this->_args[$name] = $value;
        return $this;
    }
    
    public function getArg($name, $default=null) {
        if(array_key_exists($name, $this->_args)) {
            return $this->_args[$name];
        }
        
        return $default;
    }
    
    public function hasArg($name) {
        return array_key_exists($name, $this->_args);
    }
    
    public function removeArg($name) {
        unset($this->_args[$name]);
        return $this;
    }
    
    public function setArgs(array $args) {
        $this->_args = array();
        return $this->addArgs($args);
    }
    
    public function addArgs(array $args) {
        $this->_args = array_merge($this->_args, $args);
        return $this;
    }
    
    public function getArgs(array $add=array()) {
        $output = $this->_args;
        
        foreach($add as $key => $var) {
            $output[$key] = $var;
        }
        
        return $output;
    }
    
    public function offsetSet($name, $value) {
        return $this->setArg($name, $value);
    }
    
    public function offsetGet($name) {
        return $this->getArg($name);
    }
    
    public function offsetExists($name) {
        return $this->hasArg($name);
    }
    
    public function offsetUnset($name) {
        return $this->removeArg($name);
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'path' => $this->_path,
            'location' => $this->_location,
            'args' => count($this->_args),
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
