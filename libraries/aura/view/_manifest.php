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
class HelperNotFoundException extends RuntimeException {}


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


interface IRenderTarget extends arch\IContextAware {
    public function getView();
}


interface IArgContainer {
    public function setArgs(array $args);
    public function addArgs(array $args);
    public function getArgs(array $add=array());
    public function setArg($name, $value);
    public function getArg($name, $default=null);
    public function hasArg($name);
    public function removeArg($name);
    
}


trait TArgContainer {
    
    protected $_args = array();
    
    public function setArgs(array $args) {
        $this->_args = array();
        return $this->addArgs($args);
    }
    
    public function addArgs(array $args) {
        foreach($args as $key => $value){
            $this->setArg($key, $value);
        }
        
        return $this;
    }
    
    public function getArgs(array $add=array()) {
        return array_merge($this->_args, $add);
    }
    
    public function setArg($key, $value) {
        $this->_args[$key] = $value;
        return $this;
    }
    
    public function getArg($key, $default=null) {
        if(isset($this->_args[$key])) {
            return $this->_args[$key];
        }
        
        return $default;
    }
    
    public function removeArg($key) {
        unset($this->_args[$key]);
        return $this;
    }
    
    public function hasArg($key) {
        return isset($this->_args[$key]);
    }
}



interface IContentProvider extends 
    IDeferredRenderable, 
    IArgContainer,
    arch\IProxyResponse 
    {}

interface IContentConsumer {
    public function setContentProvider(IContentProvider $provider);
    public function getContentProvider();
}


interface IView extends 
    IContentConsumer, 
    IRenderTarget, 
    halo\protocol\http\IStringResponse, 
    IArgContainer, 
    \ArrayAccess, 
    core\IHelperProvider, 
    core\string\IStringEscapeHandler,
    core\i18n\translate\ITranslationProxy
{
    public function getType();
    public function render();
    public function newErrorContainer(\Exception $e);
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
            return aura\theme\Base::factory($this->_context);
        }
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
            return static::DEFAULT_LAYOUT;
        }
        
        return $this->_layout;
    }
}


interface IHtmlView extends ILayoutView {
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
    
    // Favicon
    public function setFaviconHref($url);
    public function getFaviconHref();
    public function linkFavicon($uri);
    
    // CSS
    public function linkCss($uri, $media=null, $weight=null, array $attributes=null, $condition=null);
    public function linkConditionalCss($condition, $uri, $media=null, $weight=null, array $attributes=null);
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
    public function shouldRenderIELegacyNotice($flag=null);
}


interface IHelper extends core\IHelper {}


interface ITemplate extends IContentProvider, \ArrayAccess, IRenderTarget, core\i18n\translate\ITranslationProxy {
    // Escaping
    public function esc($value, $default=null);
    public function escAttribute($name, $default=null);
    
    // Helpers
    public function __get($member);
}