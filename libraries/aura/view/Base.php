<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view;

use df;
use df\core;
use df\aura;
use df\arch;
use df\halo;

class Base implements IView {
    
    use core\TContextAware;
    use core\THelperProvider;
    use core\string\THtmlStringEscapeHandler;
    use core\TStringProvider;
    use core\lang\TChainable;
    
    public $content;
    
    public static function factory($type, arch\IContext $context) {
        $type = ucfirst($type);
        $class = 'df\\aura\\view\\'.$type;
        
        if(!class_exists($class)) {
            $class = 'df\\aura\\view\\Generic';
        }
        
        return new $class($type, $context);
    }
    
    public function __construct($type, arch\IContext $context) {
        $this->_type = $type;
        $this->context = $context;
    }
    
    
// Content
    public function getType() {
        return $this->_type;
    }
    
    public function setContentProvider(IContentProvider $provider) {
        $this->content = $provider;
        $this->content->setRenderTarget($this);
        return $this;
    }
    
    public function getContentProvider() {
        return $this->content;
    }

    public function toString() {
        return $this->render();
    }

    
// Args
    public function setArgs(array $args) {
        $this->_checkContentProvider();
        $this->content->setArgs($args);
        return $this;
    }
    
    public function addArgs(array $args) {
        $this->_checkContentProvider();
        $this->content->addArgs($args);
        return $this;
    }
    
    public function getArgs(array $add=[]) {
        $this->_checkContentProvider();
        return $this->content->getArgs($add);
    }
    
    public function setArg($key, $value) {
        $this->_checkContentProvider();
        $this->content->setArg($key, $value);
        return $this;
    }
    
    public function getArg($key, $default=null) {
        $this->_checkContentProvider();
        return $this->content->getArg($key, $default);
    }
    
    public function removeArg($key) {
        $this->_checkContentProvider();
        $this->content->removeArg($key);
        return $this;
    }
    
    public function hasArg($key) {
        $this->_checkContentProvider();
        return $this->content->hasArg($key);
    }
    
    public function offsetSet($name, $value) {
        $this->_checkContentProvider();
        $this->content->setArg($name, $value);
        return $this;
    }
    
    public function offsetGet($name) {
        $this->_checkContentProvider();
        return $this->content->getArg($name);
    }
    
    public function offsetExists($name) {
        $this->_checkContentProvider();
        return $this->content->hasArg($name);
    }
    
    public function offsetUnset($name) {
        $this->_checkContentProvider();
        $this->content->removeArg($name);
        return $this;
    }
    

// Render
    public function getView() {
        return $this;
    }
    
    public function render() {
        $this->_beforeRender();
        $innerContent = null;

        if($this->content) {
            $innerContent = $this->content->renderTo($this);
        }
        
        $output = $innerContent;

        if($this instanceof ILayoutView && $this->shouldUseLayout()) {
            try {
                $layout = aura\view\content\Template::loadLayout($this, $innerContent);
                $output = $layout->renderTo($this);
            } catch(aura\view\ContentNotFoundException $e) {}
        }

        if($this instanceof IThemedView) {
            $this->getTheme()->renderTo($this);
        }

        return $output;
    }
    
    protected function _beforeRender() {}
    
    private function _checkContentProvider() {
        if(!$this->content) {
            throw new RuntimeException(
                'No content provider has been set for '.$this->_type.' type view',
                404
            );
        }
    }
    
    
// Helpers
    protected function _loadHelper($name) {
        return $this->context->loadRootHelper($name, $this);
    }
    
    
    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        return $this->context->_($phrase, $data, $plural, $locale);
    }
}
