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

interface IDeferredRenderable extends IRenderable, core\IStringProvider {
    public function setRenderTarget(IRenderTarget $target=null);
    public function getRenderTarget();
}

interface IRenderTarget extends arch\IContextAware {
    public function getView();
}


interface IContentProvider extends IDeferredRenderable, core\IAttributeContainer, \ArrayAccess {}

interface IContentConsumer {
    public function setContentProvider(IContentProvider $provider);
    public function getContentProvider();
}


interface IView extends 
    IContentConsumer, 
    IRenderTarget, 
    halo\protocol\http\IStringResponse, 
    core\IAttributeContainer, 
    \ArrayAccess, 
    core\IHelperProvider, 
    core\string\IStringEscapeHandler,
    core\i18n\translate\ITranslationProxy
{
    public function getType();
    public function render();
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
    public function linkCss($uri, $media=null, $weight=50, array $attributes=array());
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
    public function linkJs($uri, $weight=50, array $attributes=null);
    public function getJs();
    public function clearJs();
    
    // Scripts
    public function addScript($script);
    public function removeScript($script);
    public function useJsEnabledScript($flag=null);
    
    
    // Rendering
    public function shouldRenderBase();
}


interface IHelper extends core\IHelper {}


interface ITemplate extends IContentProvider, IRenderTarget, core\i18n\translate\ITranslationProxy {
    // Escaping
    public function esc($value, $default=null);
    public function escAttribute($name, $default=null);
    
    // Helpers
    public function __get($member);
}