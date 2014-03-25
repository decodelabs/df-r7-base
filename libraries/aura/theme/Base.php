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

class Base implements ITheme {
    
    const COOKIE_NOTICE = false;
    const COOKIE_NOTICE_COOKIE = 'cnx';

    protected $_id;
    protected $_iconMap = null;
    
    public static function factory($id) {
        if($id instanceof ITheme) {
            return $id;
        } else if($id instanceof arch\IContext) {
            $context = $id;
            $config = Config::getInstance($context->getApplication());
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
        
        return $this;
    }
    
    public function renderToHtml(aura\view\IHtmlView $view) {
        $this->applyDefaultIncludes($view);
        $this->applyDefaultViewTitle($view);
        $this->applyDefaultBodyTagData($view);

        if(static::COOKIE_NOTICE && !$view->context->http->getCookie(static::COOKIE_NOTICE_COOKIE)) {
            $this->applyCookieNotice($view);
        }
    }

    public function applyDefaultIncludes(aura\view\IView $view) {
        // stub
    }
    
    public function applyDefaultViewTitle(aura\view\IView $view) {
        if(!$view->hasTitle()) {
            $breadcrumbs = $view->getContext()->navigation->getBreadcrumbs();
            $parts = [];

            foreach($breadcrumbs->getEntries() as $entry) {
                array_unshift($parts, $entry->getBody());
            }
            
            if(!empty($parts)) {
                $view->setTitle(implode(' < ', $parts));
            }
        }
        
        if(!$view->hasTitleSuffix()) {
            $suffix = $view->getContext()->getApplication()->getName();
            
            if($view->hasTitle()) {
                $suffix = ' : '.$suffix;
            }
            
            $view->setTitleSuffix($suffix);
        }
    }

    public function applyDefaultBodyTagData(aura\view\IView $view) {
        $request = $view->getContext()->request;
        
        $view->getBodyTag()
            ->setDataAttribute('location', $request->getLiteralPathString())
            ->setDataAttribute('layout', $view->getLayout())
            ->setDataAttribute('base', '/'.ltrim($view->application->getBaseUrl()->getPathString(), '/'));
    }

    public function applyCookieNotice(aura\view\IView $view) {
        $view->slot->set('cookieNotice', 'elements/CookieNotice.html', '~front/');
    }


// Assets
    public function findAsset(core\IApplication $application, $path) {
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
}