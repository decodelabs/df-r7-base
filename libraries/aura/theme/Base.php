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

class Base implements ITheme, core\IDumpable {
    
    const APPLICATION_IMAGE = 'app.png';
    const APPLICATION_COLOR = 'white';

    protected $_id;
    protected $_iconMap = null;
    protected $_facets = ['analytics', 'touchIcons'];

    public static function factory($id) {
        if($id instanceof ITheme) {
            return $id;
        } else if($id instanceof arch\IContext) {
            $context = $id;
            $config = Config::getInstance();
            $id = $config->getThemeIdFor($context->location->getArea());
        }
        
        $id = lcfirst($id);
        $class = 'df\\apex\\themes\\'.$id.'\\Theme';
        
        if(!class_exists($class)) {
            $class = __CLASS__;
        }
        
        return new $class($id);
    }
    
    protected function __construct($id) {
        $this->_id = $id;

        $facets = [];

        if(is_array($this->_facets)) {
            $facets = $this->_facets;
        }

        $this->_facets = [];

        foreach($facets as $name) {
            $this->loadFacet($name);
        }
    }
    
    public function getId() {
        return $this->_id;
    }
    
    
// Renderable
    public function renderTo(aura\view\IRenderTarget $target) {
        $view = $target->getView();
        $func = 'renderTo'.$view->getType();
        
        if(method_exists($this, $func)) {
            $this->$func($view);
        }

        foreach($this->_facets as $facet) {
            $facet->renderTo($view);
        }
        
        return $this;
    }
    
    public function renderToHtml(aura\view\IHtmlView $view) {
        $this->applyDefaultIncludes($view);
        $this->applyDefaultViewTitle($view);
        $this->applyDefaultBodyTagData($view);
        $this->applyDefaultMetaData($view);
    }

    public function applyDefaultIncludes(aura\view\IView $view) {
        // stub
    }
    
    public function applyDefaultViewTitle(aura\view\IView $view) {
        if(!$view->hasTitle()) {
            $breadcrumbs = $view->getContext()->apex->breadcrumbs();
            $parts = [];

            foreach($breadcrumbs->getEntries() as $entry) {
                array_unshift($parts, $entry->getBody());
            }
            
            if(!empty($parts)) {
                $view->setTitle(implode(' < ', $parts));
            }
        }
        
        if(!$view->hasTitleSuffix()) {
            $suffix = df\Launchpad::$application->getName();
            
            if($view->hasTitle()) {
                $suffix = ' : '.$suffix;
            }
            
            $view->setTitleSuffix($suffix);
        }
    }

    public function applyDefaultBodyTagData(aura\view\IView $view) {
        $request = $view->getContext()->request;
        $router = core\application\http\Router::getInstance();
        
        $view->getBodyTag()
            ->setDataAttribute('location', $request->getLiteralPathString())
            ->setDataAttribute('layout', $view->getLayout())
            ->setDataAttribute('base', '/'.ltrim($router->getBaseUrl()->getPathString(), '/'));

        if(df\Launchpad::COMPILE_TIMESTAMP) {
            $view->getBodyTag()->setDataAttribute('cts', df\Launchpad::COMPILE_TIMESTAMP);
        } else if($view->context->application->isDevelopment()) {
            $view->getBodyTag()->setDataAttribute('cts', time());
        }
    }

    public function applyDefaultMetaData(aura\view\IView $view) {
        if(!$view->hasMeta('msapplication-config')) {
            $view->setMeta('msapplication-config', 'none');
        }

        if(!$view->hasMeta('msapplication-TileColor')) {
            $view->setMeta('msapplication-TileColor', $this->getApplicationColor());
        }

        if(!$view->hasMeta('application-name')) {
            $view->setMeta('application-name', $view->getContext()->application->getName());
        }
    }


// Assets
    public function findAsset($path) {
        $output = df\Launchpad::$loader->findFile(
            $lookupPath = 'apex/themes/'.$this->getId().'/assets/'.$path
        );

        if(!$output) {
            $output = df\Launchpad::$loader->findFile(
                $lookupPath = 'apex/themes/shared/assets/'.$path
            );
        }

        return $output;
    }

    public function getApplicationImagePath() {
        return static::APPLICATION_IMAGE;
    }

    public function getApplicationColor() {
        return neon\Color::factory(static::APPLICATION_COLOR);
    }

    public function mapIcon($name) {
        if($this->_iconMap === null) {
            if(!$path = df\Launchpad::$loader->findFile('apex/themes/'.$this->getId().'/IconMap.php')) {
                $path = df\Launchpad::$loader->findFile('apex/themes/shared/IconMap.php');
            }

            if($path) {
                $this->_iconMap = require $path;
            }

            if(!is_array($this->_iconMap)) {
                $this->_iconMap = [];
            }
        }

        if(isset($this->_iconMap[$name])) {
            return $this->_iconMap[$name];
        } else {
            return null;
        }
    }

    public function mapLayout(aura\view\ILayoutView $view) {
        return null;
    }


// Facets
    public function loadFacet($name) {
        $name = lcfirst($name);

        if(!isset($this->_facets[$name])) {
            $this->_facets[$name] = aura\theme\facet\Base::factory($name);
        }

        return $this->_facets[$name];
    }

    public function hasFacet($name) {
        return isset($this->_facets[lcfirst($name)]);
    }

    public function getFacet($name) {
        $name = lcfirst($name);

        if(isset($this->_facets[$name])) {
            return $this->_facets[$name];
        }

        return null;
    }

    public function removeFacet($name) {
        unset($this->_facets[lcfirst($name)]);
        return $this;
    }

    public function getFacets() {
        return $this->_facets;
    }

// Dump
    public function getDumpProperties() {
        return [
            'id' => $this->_id,
            'facets' => $this->_facets
        ];
    }
}