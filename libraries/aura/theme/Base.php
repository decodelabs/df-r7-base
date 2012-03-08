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
    
    protected $_id;
    
    public static function factory($id) {
        if($id instanceof ITheme) {
            return $id;
        } else if($id instanceof arch\IContext) {
            $context = $id;
            $config = Config::getInstance($context->getApplication());
            $id = $config->getThemeIdFor($context->getRequest()->getArea());
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
        $this->_setDefaultViewTitle($view);
        $request = $view->getContext()->getRequest();
        
        $view->getBody()
            ->setDataAttribute('location', implode('/', $request->getLiteralPathArray()))
            ->setDataAttribute('layout', $view->getLayout());
    }
    
    protected function _setDefaultViewTitle(aura\view\IHtmlView $view) {
        if(!$view->hasTitle()) {
            // TODO: Replace title with breadcrumbs
            
            
            $request = $view->getContext()->getRequest();
            $parts = $request->getLiteralPathArray();
            
            if($request->isDefaultArea()) {
                array_shift($parts);
            }
            
            array_pop($parts);
            
            if(!$request->isDefaultAction()) {
                $parts[] = $request->getAction();
            }
            
            foreach($parts as $i => $part) {
                $parts[$i] = ucwords(
                    preg_replace('/([A-Z])/u', ' $1', str_replace(
                        array('-', '_'), ' ', ltrim($part, '~')
                    ))
                );
            }
            
            if(!empty($parts)) {
                $view->setTitle(implode(' > ', $parts));
            }
        }
        
        if(!$view->hasTitleSuffix()) {
            $suffix = $view->getApplication()->getName();
            
            if($view->hasTitle()) {
                $suffix = ' : '.$suffix;
            }
            
            $view->setTitleSuffix($suffix);
        }
    }

    public function findAsset(core\IApplication $application, $path) {
        return df\Launchpad::$loader->findFile(
            $lookupPath = 'apex/themes/'.$this->getId().'/assets/'.$path
        );
    }
}