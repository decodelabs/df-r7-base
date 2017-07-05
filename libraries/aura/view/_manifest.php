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
use df\flex;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
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


interface IDeferredRenderable extends IRenderable, IRenderTargetProvider, core\IStringProvider {
    public function render();
}


interface IRenderTarget extends core\IContextAware {
    public function getView();
}


interface ISlotContainer {
    public function setSlots(array $slots);
    public function addSlots(array $slots);
    public function getSlots();
    public function clearSlots();
    public function setSlot(string $key, $value);
    public function hasSlot(string ...$keys): bool;
    public function slotExists(string $key);
    public function checkSlots(string ...$keys);
    public function getSlot(string $key, $default=null);
    public function renderSlot(string $key, $default=null);
    public function removeSlot(string $key);
    public function esc($value): string;
}

interface ISlotProvider extends ISlotContainer {
    public function startSlotCapture($key);
    public function endSlotCapture();
    public function isCapturingSlot();
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
    flex\IStringEscapeHandler,
    core\lang\IChainable,
    arch\IAjaxDataProvider
{
    public function getType();
    public function render();
}


class Base implements IView {

    use TView;

    public static function factory($type, arch\IContext $context) {
        $type = ucfirst($type);
        $class = 'df\\aura\\view\\'.$type;

        if(!class_exists($class)) {
            $class = 'df\\aura\\view\\Generic';
        }

        return new $class($type, $context);
    }
}


interface IResponseView extends IView, link\http\IStreamResponse {}


interface IThemedView extends IView, aura\theme\IFacetProvider {
    public function setTheme($theme);
    public function getTheme();
    public function hasTheme();
}


interface ILayoutView extends IThemedView {
    public function shouldUseLayout(bool $flag=null);
    public function setLayout($layout);
    public function getLayout();
}

interface ILayoutMap {
    public function mapLayout(ILayoutView $view);
}



interface IAjaxView extends IResponseView {
    public function setRedirect($request);
    public function getRedirect();
    public function shouldForceRedirect(bool $flag=null);
    public function isComplete(bool $flag=null);
    public function shouldReload(bool $flag=null);
}



interface IHtmlView extends IResponseView, ILayoutView {
    public function getHtmlTag();
    public function getBodyTag();

    // Title
    public function setTitle(?string $title);
    public function getTitle(): ?string;
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

    // Robots
    public function canIndex(bool $flag=null, $bot='robots');
    public function canFollow(bool $flag=null, $bot='robots');
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
    public function setStyles(...$styles);
    public function addStyles(...$styles);
    public function getStyles();
    public function hasStyles();
    public function removeStyles();
    public function setStyle($selector, $styles);
    public function getStyle($selector);
    public function removeStyle(...$selectors);
    public function hasStyle(...$selectors);

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
    public function shouldRenderBase(bool $flag=null);
}


interface IImplicitViewHelper extends arch\IDirectoryHelper {}
interface IContextSensitiveHelper extends arch\IDirectoryHelper {}


interface ICascadingHelperProvider extends core\IContextAware, IRenderTargetProvider {
    public function __call($method, $args);
    public function __get($key);
}


interface ITemplate extends IContentProvider, ISlotProvider, \ArrayAccess, IRenderTarget {
    public function isRendering();
    public function isLayout();

    // Escaping
    public function esc($value): string;

    // Helpers
    public function __get($member);
}
