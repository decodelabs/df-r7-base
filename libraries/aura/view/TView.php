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
use df\flex;
use df\link;
use df\flow;



trait TView_RenderTargetProvider {

    protected $_renderTarget;

    public function setRenderTarget(IRenderTarget $target=null) {
        $this->_renderTarget = $target;
        return $this;
    }

    public function getRenderTarget() {
        if(!$this->_renderTarget) {
            throw new RuntimeException(
                'No render target has been set'
            );
        }

        return $this->_renderTarget;
    }

    public function getView() {
        return $this->getRenderTarget()->getView();
    }
}



trait TView_DeferredRenderable {

    use TView_RenderTargetProvider;

    public function renderTo(IRenderTarget $target) {
        $this->setRenderTarget($target);
        return $this->render();
    }
}



trait TView_SlotContainer {

    public function setSlots(array $slots) {
        return $this->clearSlots()->addSlots($slots);
    }

    public function addSlots(array $slots) {
        foreach($slots as $key => $value) {
            $this->setSlot($key, $value);
        }

        return $this;
    }

    public function checkSlots(string ...$keys) {
        foreach($keys as $key) {
            if(!$this->hasSlot($key)) {
                throw new RuntimeException(
                    'Slot '.$key.' has not been defined'
                );
            }
        }

        return $this;
    }

    public function renderSlot(string $key, $default=null) {
        $value = $this->getSlot($key, $default);

        if(is_callable($value)) {
            $target = $this instanceof IDeferredRenderable ?
                $this->getRenderTarget() : $this;

            $value = $value($target);
        }

        if($value instanceof \Traversable) {
            $value = iterator_to_array($value);
        }

        if(is_array($value)) {
            $value = new aura\html\ElementContent($value);
        }

        if($value instanceof IRenderable) {
            return $value->renderTo($this);
        } else if($value instanceof aura\html\IElementRepresentation) {
            return $value;
        } else {
            return $this->esc((string)$value);
        }
    }
}


trait TView {

    use TView_SlotContainer;
    use core\TContextAware;
    use core\THelperProvider;
    use flex\THtmlStringEscapeHandler;
    use core\TStringProvider;
    use core\TTranslator;
    use core\lang\TChainable;

    public $content;
    public $slots = [];

    protected $_slotCaptureKey = null;
    protected $_type;

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


    public function setSlot(string $key, $value) {
        $this->slots[$key] = $value;
        return $this;
    }

    public function hasSlot(string $key) {
        return isset($this->slots[$key]);
    }

    public function slotExists(string $key) {
        return array_key_exists($key, $this->slots);
    }

    public function getSlot(string $key, $default=null) {
        if(isset($this->slots[$key])) {
            return $this->slots[$key];
        } else {
            return $default;
        }
    }

    public function removeSlot(string $key) {
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


    public function translate(array $args) {
        return $this->context->i18n->translate($args);
    }
}



trait TView_Response {

    use link\http\TStringResponse;
    use core\lang\TChainable;

    protected $_renderedContent = null;

    public function onDispatchComplete() {
        if($this->_renderedContent === null) {
            $this->_renderedContent = $this->render();
        }

        $this->prepareHeaders();
        return $this;
    }

    public function getContent() {
        if($this->_renderedContent === null) {
            $this->_renderedContent = $this->render();
        }

        return $this->_renderedContent;
    }

    public function setContentType($type) {
        throw new RuntimeException(
            'View content type cannot be changed'
        );
    }

    public function getContentType() {
        return core\fs\Type::extToMime($this->_type);
    }
}



trait TView_Themed {

    protected $_theme;

    public function setTheme($theme) {
        if($theme === null) {
            $this->_theme = null;
        } else {
            $this->_theme = aura\theme\Base::factory($theme);
        }

        return $this;
    }

    public function getTheme() {
        if($this->_theme === null) {
            $this->_theme = aura\theme\Base::factory($this->context);
        }

        return $this->_theme;
    }

    public function hasTheme() {
        return $this->_theme !== null;
    }


    public function loadFacet($name, $callback=null) {
        $this->getTheme()->loadFacet($name, $callback);
        return $this;
    }

    public function hasFacet($name) {
        return $this->getTheme()->hasFacet($name);
    }

    public function getFacet($name) {
        return $this->getTheme()->getFacet($name);
    }

    public function removeFacet($name) {
        $this->getTheme()->removeFacet($name);
        return $this;
    }

    public function getFacets() {
        return $this->getTheme()->getFacets();
    }
}



trait TView_Layout {

    use TView_Themed;

    protected $_layout;
    protected $_useLayout = true;

    public function shouldUseLayout(bool $flag=null) {
        if($flag !== null) {
            $this->_useLayout = $flag;
            return $this;
        }

        return $this->_useLayout;
    }

    public function setLayout($layout) {
        $this->_layout = ucfirst(flex\Text::formatId($layout));
        return $this;
    }

    public function getLayout() {
        if($this->_layout === null) {
            if($this instanceof IThemedView) {
                $theme = $this->getTheme();

                if($theme instanceof ILayoutMap) {
                    $theme->mapLayout($this);
                }
            }

            if($this->_layout === null) {
                $this->_layout = static::DEFAULT_LAYOUT;
            }
        }

        return $this->_layout;
    }
}





trait TView_DirectoryHelper {

    public $view;

    protected function _handleHelperTarget($target) {
        if($target instanceof IView) {
            $this->view = $target;
        } else if($target instanceof IRenderTargetProvider
        || method_exists($target, 'getView')) {
            $this->view = $target->getView();
        } else if(isset($target->view)) {
            $this->view = $target->view;
        } else if($this instanceof IImplicitViewHelper) {
            throw new RuntimeException(
                'Cannot use implicit view helper from objects that do not provide a view'
            );
        }
    }
}



trait TView_CascadingHelperProvider {

    use core\TTranslator;

    public $view;

    public function __call($method, $args) {
        $output = $this->_getHelper($method, true);

        if(!is_callable($output)) {
            throw new RuntimeException(
                'Helper '.$method.' is not callable'
            );
        }

        return $output(...$args);
    }

    public function __get($key) {
        return $this->_getHelper($key);
    }

    private function _getHelper($key, $callable=false) {
        if(!$this->view && method_exists($this, 'getView')) {
            $this->view = $this->getView();
        }

        if(isset($this->{$key})) {
            return $this->{$key};
        }

        $context = $this->getContext();

        if($key == 'context') {
            return $context;
        }

        if($this->view && ($output = $this->view->getHelper($key, true))) {
            if($output instanceof IContextSensitiveHelper) {
                // Inject current context into view helper
                $output = clone $output;
                $output->context = $context;
            }
        } else if($callable) {
            return [$context, $key];
        } else {
            $output = $context->{$key};
        }

        $this->{$key} = $output;
        return $output;
    }

    public function translate(array $args) {
        if($this->view) {
            return $this->view->i18n->translate($args);
        } else {
            return $this->getContext()->i18n->translate($args);
        }
    }
}