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
    
    use core\TContextAwarePublic;
    use core\THelperProvider;
    use core\string\THtmlStringEscapeHandler;
    use core\TStringProvider;
    use core\lang\TChainable;
    
    public $contentProvider;
    
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
        $this->contentProvider = $provider;
        return $this;
    }
    
    public function getContentProvider() {
        return $this->contentProvider;
    }

    public function toString() {
        return $this->render();
    }

    
// Args
    public function setArgs(array $args) {
        $this->_checkContentProvider();
        $this->contentProvider->setArgs($args);
        return $this;
    }
    
    public function addArgs(array $args) {
        $this->_checkContentProvider();
        $this->contentProvider->addArgs($args);
        return $this;
    }
    
    public function getArgs(array $add=[]) {
        $this->_checkContentProvider();
        return $this->contentProvider->getArgs($add);
    }
    
    public function setArg($key, $value) {
        $this->_checkContentProvider();
        $this->contentProvider->setArg($key, $value);
        return $this;
    }
    
    public function getArg($key, $default=null) {
        $this->_checkContentProvider();
        return $this->contentProvider->getArg($key, $default);
    }
    
    public function removeArg($key) {
        $this->_checkContentProvider();
        $this->contentProvider->removeArg($key);
        return $this;
    }
    
    public function hasArg($key) {
        $this->_checkContentProvider();
        return $this->contentProvider->hasArg($key);
    }
    
    public function offsetSet($name, $value) {
        $this->_checkContentProvider();
        $this->contentProvider->setArg($name, $value);
        return $this;
    }
    
    public function offsetGet($name) {
        $this->_checkContentProvider();
        return $this->contentProvider->getArg($name);
    }
    
    public function offsetExists($name) {
        $this->_checkContentProvider();
        return $this->contentProvider->hasArg($name);
    }
    
    public function offsetUnset($name) {
        $this->_checkContentProvider();
        $this->contentProvider->removeArg($name);
        return $this;
    }
    

// Render
    public function getView() {
        return $this;
    }
    
    public function render() {
        $this->_beforeRender();
        $innerContent = null;

        if($this->contentProvider) {
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
        if(!$this->contentProvider) {
            throw new RuntimeException(
                'No content provider has been set for '.$this->_type.' type view',
                404
            );
        }
    }
    
    
// Helpers
    protected function _loadHelper($name) {
        return $this->context->_getDefaultHelper($name, $this);
    }
    
    
    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        return $this->context->_($phrase, $data, $plural, $locale);
    }

    public function newErrorContainer(\Exception $e) {
        return new aura\view\content\ErrorContainer($this, $e);
    }
}
