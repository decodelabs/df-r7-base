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
use df\link;
use df\flow;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class ContentNotFoundException extends RuntimeException {}
class BadMethodCallException extends \BadMethodCallException implements IException {}


// Interfaces
interface IRenderable {
    public function renderTo(IRenderTarget $target);
}

interface IRenderTargetProvider {
    public function setRenderTarget(IRenderTarget $target=null);
    public function getRenderTarget();
    public function getView();
}


trait TRenderTargetProvider {

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


interface IDeferredRenderable extends IRenderable, IRenderTargetProvider, core\IStringProvider {
    public function render();
}



trait TDeferredRenderable {

    use TRenderTargetProvider;

    public function renderTo(IRenderTarget $target) {
        $this->setRenderTarget($target);
        return $this->render();
    }
}


interface IRenderTarget extends core\IContextAware {
    public function getView();
}



interface ISlotContainer {
    public function setSlots(array $slots);
    public function addSlots(array $slots);
    public function getSlots();
    public function clearSlots();
    public function setSlot($key, $value);
    public function hasSlot($key);
    public function slotExists($key);
    public function getSlot($key, $default=null);
    public function renderSlot($key, $default=null);
    public function removeSlot($key);
    public function esc($value);
}

interface ISlotProvider extends ISlotContainer {
    public function startSlotCapture($key);
    public function endSlotCapture();
    public function isCapturingSlot();
}

trait TSlotContainer {

    public function setSlots(array $slots) {
        return $this->clearSlots()->addSlots($slots);
    }

    public function addSlots(array $slots) {
        foreach($slots as $key => $value) {
            $this->setSlot($key, $value);
        }

        return $this;
    }

    public function renderSlot($key, $default=null) {
        $value = $this->getSlot($key, $default);

        if(is_callable($value)) {
            $value = call_user_func_array($value, [$this]);
        }

        if($value instanceof IRenderable) {
            return $value->renderTo($this);
        } else if($value instanceof aura\html\IElementRepresentation) {
            return $value->toString();
        } else {
            return $this->esc((string)$value);
        }
    }
}



interface IContentProvider extends
    IDeferredRenderable,
    arch\IProxyResponse
    {}

interface ICollapsibleContentProvider extends IContentProvider {
    public function collapse();
}

interface IContentConsumer {
    public function setContentProvider(IContentProvider $provider);
    public function getContentProvider();
}


interface IViewRenderEventReceiver {
    public function beforeViewRender(IView $view);
    public function onViewContentRender(IView $view, $content);
    public function onViewLayoutRender(IView $view, $content);
    public function afterViewRender(IView $view, $content);
}


interface IView extends
    IContentConsumer,
    IRenderTarget,
    ISlotProvider,
    \ArrayAccess,
    core\IHelperProvider,
    core\string\IStringEscapeHandler,
    core\lang\IChainable
{
    public function getType();
    public function render();
}


interface IResponseView extends IView, link\http\IStreamResponse {}

trait TResponseView {

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


interface IThemedView extends IView {
    public function setTheme($theme);
    public function getTheme();
    public function hasTheme();
}

trait TThemedView {

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
}


interface ILayoutView extends IThemedView {
    public function shouldUseLayout($flag=null);
    public function setLayout($layout);
    public function getLayout();
}

trait TLayoutView {

    use TThemedView;

    protected $_layout;
    protected $_useLayout = true;

    public function shouldUseLayout($flag=null) {
        if($flag !== null) {
            $this->_useLayout = (bool)$flag;
            return $this;
        }

        return $this->_useLayout;
    }

    public function setLayout($layout) {
        $this->_layout = ucfirst(core\string\Manipulator::formatId($layout));
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


interface ILayoutMap {
    public function mapLayout(ILayoutView $view);
}

interface INotificationProxyView extends IView, ILayoutView, flow\INotificationProxy {}

interface IHtmlView extends IResponseView, ILayoutView, INotificationProxyView {
    public function getHtmlTag();
    public function getBodyTag();

    // Title
    public function setTitle($title);
    public function getTitle();
    public function hasTitle();
    public function setTitlePrefix($prefix);
    public function getTitlePrefix();
    public function setTitleSuffix($suffix);
    public function getTitleSuffix();
    public function setFullTitle($title);
    public function getFullTitle();

    // Base
    public function setBaseHref($url);
    public function getBaseHref();

    // Meta
    public function setMeta($name, $value);
    public function getMeta($name);
    public function hasMeta($name);
    public function removeMeta($name);

    public function setData($key, $value);
    public function getData($key);
    public function hasData($key);
    public function removeData($key);

    // Keywords
    public function setKeywords($keywords);
    public function addKeywords($keywords);
    public function getKeywords();
    public function hasKeywords();
    public function hasKeyword($keyword);
    public function removeKeyword($keyword);
    public function removeKeywords();


    // Robots
    public function canIndex($flag=null, $bot='robots');
    public function canFollow($flag=null, $bot='robots');
    public function setRobots($value);
    public function getRobots();
    public function hasRobots();
    public function removeRobots();

    // Link
    public function addLink($id, $rel, $url, array $attributes=null);
    public function getLinks();
    public function getLink($id);
    public function removeLink($id);
    public function clearLinks();

    // Favicon
    public function setFaviconHref($url);
    public function getFaviconHref();
    public function linkFavicon($uri);

    // CSS
    public function linkCss($uri, $weight=null, array $attributes=null, $condition=null);
    public function linkConditionalCss($condition, $uri, $weight=null, array $attributes=null);
    public function getCss();
    public function clearCss();

    // Styles
    public function setStyles($styles);
    public function addStyles($styles);
    public function getStyles();
    public function hasStyles();
    public function removeStyles();
    public function setStyle($selector, $styles);
    public function getStyle($selector);
    public function removeStyle($selector);
    public function hasStyle($selector);

    // Js
    public function linkJs($uri, $weight=null, array $attributes=null, $condition=null);
    public function linkConditionalJs($condition, $uri, $weight=null, array $attributes=null);
    public function linkFootJs($uri, $weight=null, array $attributes=null, $condition=null);
    public function linkConditionalFootJs($condition, $uri, $weight=null, array $attributes=null);
    public function getJs();
    public function getHeadJs();
    public function getFootJs();
    public function clearJs();
    public function clearHeadJs();
    public function clearFootJs();

    // Scripts
    public function addScript($id, $script, $condition=null);
    public function addHeadScript($id, $script, $condition=null);
    public function addFootScript($id, $script, $condition=null);
    public function removeScript($id);
    public function removeHeadScript($id);
    public function removeFootScript($id);
    public function clearScripts();
    public function clearHeadScripts();
    public function clearFootScripts();


    // Rendering
    public function shouldRenderBase($flag=null);
}


interface IImplicitViewHelper extends arch\IDirectoryHelper {}
interface IContextSensitiveHelper extends arch\IDirectoryHelper {}


trait TViewAwareDirectoryHelper {

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





interface ICascadingHelperProvider extends core\IContextAware, IRenderTargetProvider {
    public function __call($method, $args);
    public function __get($key);
}

trait TCascadingHelperProvider {

    use core\TTranslator;

    public $view;

    public function __call($method, $args) {
        $output = $this->_getHelper($method, true);

        if(!is_callable($output)) {
            throw new RuntimeException(
                'Helper '.$method.' is not callable'
            );
        }

        return call_user_func_array($output, $args);
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


interface ITemplate extends IContentProvider, ISlotProvider, \ArrayAccess, IRenderTarget {
    public function isRendering();
    public function isLayout();

    // Escaping
    public function esc($value, $default=null);

    // Helpers
    public function __get($member);
}
