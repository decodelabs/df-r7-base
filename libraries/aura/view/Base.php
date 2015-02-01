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
    use TSlotContainer;
    
    public $content;
    public $slots = [];
    protected $_slotCaptureKey = null;
    
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



// Slots
    public function getSlots() {
        return $this->slots;
    }

    public function clearSlots() {
        $this->slots = [];
        return $this;
    }


    public function setSlot($key, $value) {
        $this->slots[$key] = $value;
        return $this;
    }

    public function hasSlot($key) {
        return isset($this->slots[$key]);
    }

    public function getSlot($key, $default=null) {
        if(isset($this->slots[$key])) {
            return $this->slots[$key];
        } else {
            return $default;
        }
    }

    public function removeSlot($key) {
        unset($this->_slots[$key]);
        return $this;
    }


    public function startSlotCapture($key) {
        if($this->_slotCaptureKey !== null) {
            $this->endSlotCapture();
        }

        $this->_slotCaptureKey = $key;
        ob_start();

        return $this;
    }

    public function endSlotCapture() {
        if($this->_slotCaptureKey === null) {
            return;
        }

        $content = ob_get_clean();
        $content = $this->_normalizeSlotContent($content);

        $this->setSlot($this->_slotCaptureKey, $content);
        $this->_slotCaptureKey = null;

        return $this;
    }

    protected function _normalizeSlotContent($content) {
        return $content;
    }

    public function isCapturingSlot() {
        return $this->_slotCaptureKey !== null;
    }


    public function offsetSet($key, $value) {
        $this->setSlot($key, $value);
        return $this;
    }
    
    public function offsetGet($key) {
        return $this->getSlot($key);
    }
    
    public function offsetExists($key) {
        return $this->hasSlot($key);
    }
    
    public function offsetUnset($key) {
        $this->removeSlot($key);
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
        
        $output = $innerContent = $this->_onContentRender($innerContent);

        if($this instanceof ILayoutView && $this->shouldUseLayout()) {
            try {
                $layout = aura\view\content\Template::loadLayout($this, $innerContent);
                $output = $layout->renderTo($this);
            } catch(aura\view\ContentNotFoundException $e) {}

            $output = $this->_onLayoutRender($output);
        }

        return $this->_afterRender($output);
    }
    
    protected function _beforeRender() {
        if($this instanceof IThemedView) {
            $this->getTheme()->beforeViewRender($this);
        }
    }

    protected function _onContentRender($content) {
        if($this instanceof IThemedView) {
            // apply facets
            $content = $this->getTheme()->onViewContentRender($this, $content);
        }

        return $content;
    }

    protected function _onLayoutRender($content) {
        if($this instanceof IThemedView) {
            $content = $this->getTheme()->onViewLayoutRender($this, $content);
        }

        return $content;
    }

    protected function _afterRender($content) {
        if($this instanceof IThemedView) {
            $content = $this->getTheme()->afterViewRender($this, $content);
        }

        return $content;
    }
    
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
