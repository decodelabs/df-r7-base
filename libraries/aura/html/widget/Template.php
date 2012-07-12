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
    protected $_contextRequest;
    protected $_args = array();
    
    public function __construct(arch\IContext $context, $path, $contextRequest=null) {
        $this->setPath($path);
        $this->setContextRequest($contextRequest);
    }
    
    protected function _render() {
        $tag = $this->getTag();
        $renderTarget = $this->getRenderTarget();
        $view = $renderTarget->getView();
        
        $context = $view->getContext()->spawnInstance($this->_contextRequest);
        $template = aura\view\content\Template::loadDirectoryTemplate($context, $this->_path);
        
        if(!empty($this->_args)) {
            $template->setArgs($this->_args);
        }
        
        return $tag->renderWith(
            new aura\html\ElementString(
                $template->renderTo($renderTarget)
            )
        );
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
    public function setContextRequest($contextRequest) {
        $this->_contextRequest = $contextRequest;
        return $this;
    }
    
    public function getContextRequest() {
        return $this->_contextRequest;
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
            'contextRequest' => $this->_contextRequest,
            'args' => count($this->_args),
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
