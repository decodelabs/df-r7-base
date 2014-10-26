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
    
    protected $_contentProvider;
    
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
        $this->_context = $context;
    }
    
    
// Content
    public function getType() {
        return $this->_type;
    }
    
    public function setContentProvider(IContentProvider $provider) {
        $this->_contentProvider = $provider;
        return $this;
    }
    
    public function getContentProvider() {
        return $this->_contentProvider;
    }

    public function toString() {
        return $this->render();
    }

    
// Args
    public function setArgs(array $args) {
        $this->_checkContentProvider();
        $this->_contentProvider->setArgs($args);
        return $this;
    }
    
    public function addArgs(array $args) {
        $this->_checkContentProvider();
        $this->_contentProvider->addArgs($args);
        return $this;
    }
    
    public function getArgs(array $add=[]) {
        $this->_checkContentProvider();
        return $this->_contentProvider->getArgs($add);
    }
    
    public function setArg($key, $value) {
        $this->_checkContentProvider();
        $this->_contentProvider->setArg($key, $value);
        return $this;
    }
    
    public function getArg($key, $default=null) {
        $this->_checkContentProvider();
        return $this->_contentProvider->getArg($key, $default);
    }
    
    public function removeArg($key) {
        $this->_checkContentProvider();
        $this->_contentProvider->removeArg($key);
        return $this;
    }
    
    public function hasArg($key) {
        $this->_checkContentProvider();
        return $this->_contentProvider->hasArg($key);
    }
    
    public function offsetSet($name, $value) {
        $this->_checkContentProvider();
        $this->_contentProvider->setArg($name, $value);
        return $this;
    }
    
    public function offsetGet($name) {
        $this->_checkContentProvider();
        return $this->_contentProvider->getArg($name);
    }
    
    public function offsetExists($name) {
        $this->_checkContentProvider();
        return $this->_contentProvider->hasArg($name);
    }
    
    public function offsetUnset($name) {
        $this->_checkContentProvider();
        $this->_contentProvider->removeArg($name);
        return $this;
    }
    

// Render
    public function getView() {
        return $this;
    }
    
    public function render() {
        $this->_beforeRender();
        $innerContent = null;

        if($this->_contentProvider) {
            $innerContent = $this->getContentProvider()->renderTo($this);
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
        if(!$this->_contentProvider) {
            throw new RuntimeException(
                'No content provider has been set for '.$this->_type.' type view',
                404
            );
        }
    }
    
    
// Helpers
    public function __get($member) {
        switch($member) {
            case 'context':
                return $this->getContext();
                
            case 'application':
                return $this->_context->application;
                
            case 'contentProvider':
                return $this->getContentProvider();
                
            default:
                return $this->getHelper($member);
        }
    }

    protected function _loadHelper($name) {
        $class = 'df\\plug\\view\\'.$this->getType().$name;
            
        if(!class_exists($class)) {
            $class = 'df\\plug\\view\\'.$name;
            
            if(!class_exists($class)) {
                $class = 'df\\plug\\directory\\'.$this->_context->getRunMode().$name;

                if(!class_exists($class)) {
                    $class = 'df\\plug\\directory\\'.$name;

                    if(!class_exists($class)) {
                        return $this->_loadSharedHelper($name);
                    }
                }

                return new $class($this->_context);
            }
        }
        
        return new $class($this);
    }
    
    
    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        return $this->_context->_($phrase, $data, $plural, $locale);
    }

    public function newErrorContainer(\Exception $e) {
        return new aura\view\content\ErrorContainer($this, $e);
    }
}
