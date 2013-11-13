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



interface IContentProvider extends 
    IDeferredRenderable, 
    core\IArgContainer,
    arch\IProxyResponse 
    {}

interface IContentConsumer {
    public function setContentProvider(IContentProvider $provider);
    public function getContentProvider();
}


interface IView extends 
    IContentConsumer, 
    IRenderTarget, 
    core\IArgContainer, 
    \ArrayAccess, 
    core\IHelperProvider, 
    core\string\IStringEscapeHandler,
    core\i18n\translate\ITranslationProxy
{
    public function getType();
    public function render();
    public function newErrorContainer(\Exception $e);
}


interface IResponseView extends IView, halo\protocol\http\IStringResponse {}

trait TResponseView {

    use halo\protocol\http\TStringResponse;

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
        return core\io\Type::extToMime($this->_type);
    }
}


interface IThemedView extends IView {
    public function setTheme($theme);
    public function getTheme();
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
            $this->_theme = aura\theme\Base::factory($this->_context);
        }

        return $this->_theme;
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


interface IHtmlView extends IResponseView, ILayoutView {
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
    public function getFullTitle();
    
    // Base
    public function setBaseHref($url);
    public function getBaseHref();
    
    // Meta
    public function setMeta($name, $value);
    public function getMeta($name);
    public function hasMeta($name);
    public function removeMeta($name);
    
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
    public function linkJs($uri, $weight=null, array $attributes=null, $fallbackScript=null, $condition=null);
    public function linkConditionalJs($condition, $uri, $weight=null, array $attributes=null, $fallbackScript=null);
    public function linkHeadJs($uri, $weight=null, array $attributes=null, $fallbackScript=null, $condition=null);
    public function linkConditionalHeadJs($condition, $uri, $weight=null, array $attributes=null, $fallbackScript=null);
    public function linkFootJs($uri, $weight=null, array $attributes=null, $fallbackScript=null, $condition=null);
    public function linkConditionalFootJs($condition, $uri, $weight=null, array $attributes=null, $fallbackScript=null);
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


interface IHelper extends core\IHelper {}

trait THelper {

    protected $_view;

    public function __construct(aura\view\IView $view) {
        $this->_view = $view;
        $this->_init();
    }

    protected function _init() {}
}


interface ICascadingHelperProvider extends core\IContextAware, IRenderTargetProvider {
    public function __call($method, $args);
    public function __get($key);
}

trait TCascadingHelperProvider {

    public $view;

    public function __call($method, $args) {
        return call_user_func_array(array($this->_context, $method), $args);
    }
    
    public function __get($key) {
        if(!$this->view) {
            $this->view = $this->getView();
        }

        if($key == 'view') {
            return $this->view;
        } else if($key == 'context') {
            return $this->_context;
        }

        if($output = $this->view->getHelper($key, true)) {
            return $output;
        }

        return $this->_context->__get($key);
    }
}


interface ITemplate extends IContentProvider, \ArrayAccess, IRenderTarget, core\i18n\translate\ITranslationProxy {
    public function isRendering();
    public function isLayout();

    // Escaping
    public function esc($value, $default=null);
    public function escAttribute($name, $default=null);
    
    // Helpers
    public function __get($member);
}
