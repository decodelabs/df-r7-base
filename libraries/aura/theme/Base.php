<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\theme;

use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Spectrum\Color;
use df\arch;
use df\aura;
use df\core;
use df\fuse;

class Base implements ITheme, Dumpable
{
    public const APPLICATION_IMAGE = null;//'app.png';
    public const APPLICATION_COLOR = 'white';

    public const FACETS = [];
    public const DEFAULT_FACETS = ['touchIcons'];

    public const DEPENDENCIES = [];
    public const IMPORT_MAP = [];

    public const DEFAULT_CONTENT_CONTAINER_NAME = 'main';

    protected $_id;
    protected $_iconMap = null;
    protected $_facets = null;


    public static function factory($id): ITheme
    {
        if ($id instanceof ITheme) {
            return $id;
        } elseif (
            is_string($id) &&
            substr($id, 0, 1) == '~'
        ) {
            $id = Legacy::getThemeIdFor($id);
        } elseif ($id instanceof arch\IContext) {
            $context = $id;
            $id = Legacy::getThemeIdFor($context->location->getArea());
        }

        $id = lcfirst($id);
        $class = 'df\\apex\\themes\\' . $id . '\\Theme';

        if (!class_exists($class)) {
            $class = __CLASS__;
        }

        return new $class($id);
    }

    protected function __construct(string $id)
    {
        if (preg_match('/[^a-zA-Z0-9_]/', $id)) {
            throw Exceptional::InvalidArgument(
                'Invalid theme id'
            );
        }

        $this->_id = $id;
    }

    public function getId(): string
    {
        return $this->_id;
    }


    # Before render
    public function beforeViewRender(aura\view\IView $view)
    {
        $func = 'before' . $view->getType() . 'ViewRender';

        if (method_exists($this, $func)) {
            $this->$func($view);
        }

        foreach ($this->getFacets() as $facet) {
            $facet->beforeViewRender($view);
        }

        return $this;
    }

    public function beforeHtmlViewRender(aura\view\IView $view)
    {
        if (Genesis::$environment->isDevelopment()) {
            fuse\Manager::getInstance()->ensureDependenciesFor($this);
        }

        $this->applyDefaultIncludes($view);
        $this->applyDefaultBodyTagData($view);
    }

    public function applyDefaultIncludes(aura\view\IView $view)
    {
        // stub
    }

    public function applyDefaultBodyTagData(aura\view\IView $view)
    {
        if (!$view instanceof aura\view\IHtmlView) {
            return;
        }

        $request = $view->context->request;
        $router = Legacy::$http->getRouter();
        //$view->setData('base', '/'.ltrim($router->getBaseUrl()->getPathString(), './'));
        $view->setData('base', $router->getBaseUrl());

        if (!$router->isBaseRoot()) {
            $view->setData('root', $router->getRootUrl());
        }

        $view->getBodyTag()
            ->setDataAttribute('location', $request->getLiteralPathString())
            ->setDataAttribute('layout', $view->getLayout());

        if (Genesis::$build->shouldCacheBust()) {
            $view->setData('cts', Genesis::$build->getCacheBuster());
        }
    }


    # On content render
    public function onViewContentRender(aura\view\IView $view, $content)
    {
        $func = 'on' . $view->getType() . 'ViewContentRender';

        if (method_exists($this, $func)) {
            if (null !== ($newContent = $this->$func($view, $content))) {
                $content = $newContent;
            }
        }

        foreach ($this->getFacets() as $facet) {
            if (null !== ($newContent = $facet->onViewContentRender($view, $content))) {
                $content = $newContent;
            }
        }

        return $content;
    }


    # On layout render
    public function onViewLayoutRender(aura\view\IView $view, $content)
    {
        $func = 'on' . $view->getType() . 'ViewLayoutRender';

        if (method_exists($this, $func)) {
            if (null !== ($newContent = $this->$func($view, $content))) {
                $content = $newContent;
            }
        }

        foreach ($this->getFacets() as $facet) {
            if (null !== ($newContent = $facet->onViewLayoutRender($view, $content))) {
                $content = $newContent;
            }
        }

        return $content;
    }



    # After render
    public function afterViewRender(aura\view\IView $view, $content)
    {
        $func = 'after' . $view->getType() . 'ViewRender';

        if (method_exists($this, $func)) {
            if (null !== ($newContent = $this->$func($view, $content))) {
                $content = $newContent;
            }
        }

        foreach ($this->getFacets() as $facet) {
            if (null !== ($newContent = $facet->afterViewRender($view, $content))) {
                $content = $newContent;
            }
        }

        return $content;
    }

    public function afterHtmlViewRender(aura\view\IHtmlView $view, $content)
    {
        $this->applyDefaultViewTitle($view);
        $this->applyDefaultMetaData($view);
        return $content;
    }

    public function applyDefaultViewTitle(aura\view\IView $view)
    {
        if (!$view instanceof aura\view\IHtmlView) {
            return;
        }

        if (!$view->hasTitle()) {
            $breadcrumbs = $view->getContext()->apex->breadcrumbs();
            $parts = [];

            foreach ($breadcrumbs->getEntries() as $entry) {
                array_unshift($parts, $entry->getBody());
            }

            if (!empty($parts)) {
                $view->setTitle(implode(' < ', $parts));
            }
        }

        if (!$view->hasTitleSuffix()) {
            $suffix = Genesis::$hub->getApplicationName();

            if ($view->hasTitle()) {
                $suffix = ' : ' . $suffix;
            }

            $view->setTitleSuffix($suffix);
        }
    }

    public function applyDefaultMetaData(aura\view\IView $view)
    {
        if (!$view instanceof aura\view\IHtmlView) {
            return;
        }

        if (!$view->hasMeta('msapplication-config')) {
            $view->setMeta('msapplication-config', 'none');
        }

        if (!$view->hasMeta('msapplication-TileColor')) {
            $view->setMeta('msapplication-TileColor', $this->getApplicationColor());
        }

        if (!$view->hasMeta('application-name')) {
            $view->setMeta('application-name', Genesis::$hub->getApplicationName());
        }

        if (!$view->hasMeta('og:title')) {
            $view->setMeta('og:title', $view->getFullTitle());
        }

        if (
            !$view->hasMeta('og:description') &&
            $view->hasMeta('description')
        ) {
            $view->setMeta('og:description', $view->getMeta('description'));
        }

        if (
            !$view->hasMeta('og:url') &&
            (null !== ($canonical = $view->getCanonical()))
        ) {
            $view->setMeta('og:url', $canonical);
        }

        if (!$view->hasMeta('og:locale')) {
            $view->setMeta('og:locale', 'en_US');
        }

        if (!$view->hasMeta('og:type')) {
            $view->setMeta('og:type', 'website');
        }

        if (!$view->hasMeta('og:site_name')) {
            $view->setMeta('og:site_name', Genesis::$hub->getApplicationName());
        }


        if (!$view->hasMeta('twitter:card')) {
            if ($view->hasMeta('og:image')) {
                $view
                    ->setMeta('twitter:card', 'summary_large_image')
                    ->setMeta('twitter:image', $view->getMeta('og:image'));
            } else {
                $view->setMeta('twitter:card', 'summary');
            }
        }

        if (!$view->hasMeta('twitter:title')) {
            $view->setMeta('twitter:title', $view->getTitle());
        }

        if (
            !$view->hasMeta('twitter:description') &&
            $view->hasMeta('description')
        ) {
            $view->setMeta('twitter:description', $view->getMeta('description'));
        }
    }


    // Assets
    public function findAsset($path)
    {
        $path = core\uri\Path::normalizeLocal($path);

        $output = Legacy::getLoader()->findFile(
            $lookupPath = 'apex/themes/' . $this->getId() . '/assets/' . $path
        );

        if (!$output) {
            $output = Legacy::getLoader()->findFile(
                $lookupPath = 'apex/themes/shared/assets/' . $path
            );
        }

        return $output;
    }

    public function getApplicationImagePath()
    {
        return static::APPLICATION_IMAGE;
    }

    public function getApplicationColor()
    {
        return Color::create(static::APPLICATION_COLOR);
    }

    public function mapIcon($name)
    {
        if ($this->_iconMap === null) {
            if (!$path = Legacy::getLoader()->findFile('apex/themes/' . $this->getId() . '/IconMap.php')) {
                $path = Legacy::getLoader()->findFile('apex/themes/shared/IconMap.php');
            }

            if ($path) {
                $this->_iconMap = require $path;
            }

            if (!is_array($this->_iconMap)) {
                $this->_iconMap = [];
            }
        }

        if (isset($this->_iconMap[$name])) {
            return $this->_iconMap[$name];
        } else {
            return null;
        }
    }

    public function mapLayout(aura\view\ILayoutView $view)
    {
        return null;
    }

    public function getDependencies()
    {
        return static::DEPENDENCIES;
    }

    public function getImportMap(): array
    {
        return static::IMPORT_MAP;
    }


    // Facets
    public function loadFacet($name, $config = null)
    {
        $name = lcfirst($name);

        if (!is_array($config)) {
            $config = [];
        }

        if (!isset($this->_facets[$name])) {
            $this->_facets[$name] = aura\theme\facet\Base::factory($name, $config);
        }

        return $this->_facets[$name];
    }

    public function hasFacet($name)
    {
        $this->_loadFacets();
        return isset($this->_facets[lcfirst($name)]);
    }

    public function getFacet($name)
    {
        $this->_loadFacets();
        $name = lcfirst($name);

        if (isset($this->_facets[$name])) {
            return $this->_facets[$name];
        }

        return null;
    }

    public function removeFacet($name)
    {
        $this->_loadFacets();
        unset($this->_facets[lcfirst($name)]);
        return $this;
    }

    public function getFacets()
    {
        $this->_loadFacets();
        return $this->_facets;
    }

    protected function _loadFacets()
    {
        if ($this->_facets !== null) {
            return;
        }

        $facets = $this->_normalizeFacetList(static::DEFAULT_FACETS);

        if (null !== ($custom = $this->_getCustomFacetConfig())) {
            $facets = $this->_normalizeFacetList($custom, $facets);
        }

        $this->_facets = [];

        foreach ($facets as $name => $config) {
            $this->loadFacet($name, $config);
        }
    }

    protected function _getCustomFacetConfig(): ?array
    {
        if (is_array(static::FACETS)) {
            return static::FACETS;
        }

        return null;
    }

    protected function _normalizeFacetList(array $list, array $current = [])
    {
        foreach ($list as $name => $config) {
            if (is_string($config)) {
                $name = $config;
                $config = null;
            }

            $current[$name] = $config;
        }

        return $current;
    }


    // Container name
    public function getDefaultContentContainerName(): string
    {
        return static::DEFAULT_CONTENT_CONTAINER_NAME;
    }



    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*id' => $this->_id,
            '*facets' => $this->_facets
        ];
    }
}
