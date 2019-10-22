<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\theme;

use df;
use df\core;
use df\aura;
use df\arch;
use df\spur;
use df\neon;
use df\fuse;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Base implements ITheme, Inspectable
{
    const APPLICATION_IMAGE = null;//'app.png';
    const APPLICATION_COLOR = 'white';

    const FACETS = [];
    const DEFAULT_FACETS = ['analytics', 'touchIcons'];

    const DEPENDENCIES = [];

    protected $_id;
    protected $_iconMap = null;
    protected $_facets = null;


    public static function factory($id): ITheme
    {
        if ($id instanceof ITheme) {
            return $id;
        } elseif (is_string($id) && substr($id, 0, 1) == '~') {
            $config = Config::getInstance();
            $id = $config->getThemeIdFor($id);
        } elseif ($id instanceof arch\IContext) {
            $context = $id;
            $config = Config::getInstance();
            $id = $config->getThemeIdFor($context->location->getArea());
        }

        $id = lcfirst($id);
        $class = 'df\\apex\\themes\\'.$id.'\\Theme';

        if (!class_exists($class)) {
            $class = __CLASS__;
        }

        return new $class($id);
    }

    protected function __construct(string $id)
    {
        if (preg_match('/[^a-zA-Z0-9_]/', $id)) {
            throw core\Error::EArgument('Invalid theme id');
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
        $func = 'before'.$view->getType().'ViewRender';

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
        if (df\Launchpad::$app->isDevelopment()) {
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
        $router = core\app\runner\http\Router::getInstance();
        //$view->setData('base', '/'.ltrim($router->getBaseUrl()->getPathString(), './'));
        $view->setData('base', $router->getBaseUrl());

        if (!$router->isBaseRoot()) {
            $view->setData('root', $router->getRootUrl());
        }

        $view->getBodyTag()
            ->setDataAttribute('location', $request->getLiteralPathString())
            ->setDataAttribute('layout', $view->getLayout());

        if (df\Launchpad::$compileTimestamp) {
            $view->setData('cts', df\Launchpad::$compileTimestamp);
        } elseif ($view->context->app->isDevelopment()) {
            $view->setData('cts', time());
        }
    }


    # On content render
    public function onViewContentRender(aura\view\IView $view, $content)
    {
        $func = 'on'.$view->getType().'ViewContentRender';

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
        $func = 'on'.$view->getType().'ViewLayoutRender';

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
        $func = 'after'.$view->getType().'ViewRender';

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
            $suffix = df\Launchpad::$app->getName();

            if ($view->hasTitle()) {
                $suffix = ' : '.$suffix;
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
            $view->setMeta('application-name', df\Launchpad::$app->getName());
        }

        if (!$view->hasMeta('og:title')) {
            $view->setMeta('og:title', $view->getFullTitle());
        }

        if (!$view->hasMeta('og:description') && $view->hasMeta('description')) {
            $view->setMeta('og:description', $view->getMeta('description'));
        }
    }


    // Assets
    public function findAsset($path)
    {
        $path = core\uri\Path::normalizeLocal($path);

        $output = df\Launchpad::$loader->findFile(
            $lookupPath = 'apex/themes/'.$this->getId().'/assets/'.$path
        );

        if (!$output) {
            $output = df\Launchpad::$loader->findFile(
                $lookupPath = 'apex/themes/shared/assets/'.$path
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
        return neon\Color::factory(static::APPLICATION_COLOR);
    }

    public function mapIcon($name)
    {
        if ($this->_iconMap === null) {
            if (!$path = df\Launchpad::$loader->findFile('apex/themes/'.$this->getId().'/IconMap.php')) {
                $path = df\Launchpad::$loader->findFile('apex/themes/shared/IconMap.php');
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


    // Facets
    public function loadFacet($name, $config=null)
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

        if (is_array(static::FACETS)) {
            $facets = $this->_normalizeFacetList(static::FACETS, $facets);
        }

        $this->_facets = [];

        foreach ($facets as $name => $config) {
            $this->loadFacet($name, $config);
        }
    }

    protected function _normalizeFacetList(array $list, array $current=[])
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

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperties([
                '*id' => $inspector($this->_id),
                '*facets' => $inspector($this->_facets)
            ]);
    }
}
