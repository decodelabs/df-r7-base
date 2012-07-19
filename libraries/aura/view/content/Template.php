<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\view\content;

use df;
use df\core;
use df\aura;
use df\arch;

class Template implements aura\view\ITemplate, core\IDumpable {
        
    use arch\TContextAware;
    use aura\view\TArgContainer;
        
    private $_path;
    private $_view;
    private $_renderTarget;
    private $_isRendering = false;
    
    public static function loadDirectoryTemplate(arch\IContext $context, $path) {
        $request = $context->getRequest();
        $contextPath = $request->getDirectoryLocation();
        $lookupPath = 'apex/directory/'.$contextPath.'/_templates/'.ltrim($path, '/').'.php';
        
        if(!$absolutePath = $context->findFile($lookupPath)) {
            if(!$request->isArea('shared')) {
                $parts = explode('/', $contextPath);
                array_shift($parts);
                array_unshift($parts, 'shared');
                $contextPath = implode('/', $parts);
                $lookupPath = 'apex/directory/'.$contextPath.'/_templates/'.ltrim($path, '/').'.php';
                $absolutePath = $context->findFile($lookupPath);
            }
        }
        
        if(!$absolutePath) {
            throw new aura\view\ContentNotFoundException(
                'Template '.rtrim($request->getDirectoryLocation(), '/').'/'.$path.' could not be found'
            );
        }
        
        return new self($context, $absolutePath);
    }
    
    public static function loadLayout(aura\view\IView $view, $path) {
        $theme = $view->getTheme();
        $context = $view->getContext();
        
        $lookupPaths = array();
        $area = $context->getRequest()->getArea();
        $themeId = $theme->getId();
        
        $lookupPaths[] = 'apex/themes/'.$themeId.'/layouts/'.$area.'/'.$path.'.php';
        
        if($area !== 'shared') {
            $lookupPaths[] = 'apex/themes/'.$themeId.'/layouts/shared/'.$path.'.php';
        }
        
        if($themeId !== 'shared') {
            $lookupPaths[] = 'apex/themes/shared/layouts/'.$area.'/'.$path.'.php';
            
            if($area !== 'shared') {
                $lookupPaths[] = 'apex/themes/shared/layouts/shared/'.$path.'.php';
            }
        }
        
        foreach($lookupPaths as $testPath) {
            if($layoutPath = $context->findFile($testPath)) {
                break;
            }
        }
        
        if(!$layoutPath) {
            throw new aura\view\ContentNotFoundException(
                'Layout '.$path.' could not be found'
            );
        }
        
        return new self($context, $layoutPath);
    }
    
    public function __construct(arch\IContext $context, $absolutePath) {
        if(!is_file($absolutePath)) {
            throw new ContentNotFoundException(
                'Template '.$absolutePath.' could not be found'
            );
        }
        
        $this->_path = $absolutePath;
        $this->_context = $context;
    }
    
    
// Renderable
    public function getView() {
        if(!$this->_view) {
            throw new aura\view\RuntimeException(
                'This template is not currently rendering'
            );
        }
        
        return $this->_view;
    }
    
    public function renderTo(aura\view\IRenderTarget $target) {
        if($this->_isRendering) {
            throw new aura\view\RuntimeException('Rendering is already in progress');
        }
        
        $this->_renderTarget = $target;
        $this->_isRendering = true;
        $this->_view = $target->getView();
        
        try {
            ob_start();
            require $this->_path;
            $output = ob_get_clean();
            
            $this->_isRendering = false;
            $this->_view = null;
        } catch(\Exception $e) {
            if(ob_get_level()) {
                ob_end_clean();
            }
            
            $this->_isRendering = false;
            $this->_view = null;
            
            throw $e;
        }
        
        return $output;
    }
    
    public function setRenderTarget(aura\view\IRenderTarget $target=null) {
        $this->_renderTarget = $target;
        return $this;
    }
    
    public function getRenderTarget() {
        return $this->_renderTarget;
    }

    public function toResponse() {
        return $this->_view;
    }
    
    protected function renderInnerContent() {
        $provider = $this->getView()->getContentProvider();
        
        if($provider !== $this) {
            return $provider->renderTo($this);
        }
    }
    
    public function __toString() {
        try {
            return (string)$this->toString();
        } catch(\Exception $e) {
            core\debug()->exception($e);
            
            if($this->_view) {
                return (string)new ErrorContainer($this->_view, $e);
            } else {
                return (string)new aura\html\Element('span', $e->getMessage(), ['class' => 'state-error']);
            }
        }
    }
    
    public function toString() {
        if(!$this->_renderTarget) {
            throw new aura\view\RuntimeException(
                'No render target has been set'
            );
        }
        
        return $this->renderTo($this->_renderTarget);
    }
    
    
// Escaping
    public function esc($value, $default=null) {
        if(!$this->_view) {
            throw new aura\view\RuntimeException(
                'This template is not currently rendering'
            );
        }
        
        if($value === null) {
            $value = $default;
        }
        
        if($value instanceof ErrorContainer) {
            return $value;
        }
        
        return $this->_view->esc($value);
    }
    
    public function escAttribute($name, $default=null) {
        if(!$this->_view) {
            throw new aura\view\RuntimeException(
                'This template is not currently rendering'
            );
        }
        
        return $this->_view->esc($this->getAttribute($name, $default));
    }
    

    public function offsetSet($key, $value) {
        return $this->setArg($key, $value);
    }
    
    public function offsetGet($key) {
        return $this->getArg($key);
    }
    
    public function offsetExists($key) {
        return $this->hasArg($key);
    }
    
    public function offsetUnset($key) {
        return $this->removeArg($key);
    }


// Helpers
    public function __get($member) {
        if(!$this->_view) {
            throw new aura\view\RuntimeException(
                'This template is not currently rendering'
            );
        }
        
        switch($member) {
            case 'view':
                return $this->_view;
                
            case 'context':
                return $this->_context;
                
            default:
                return $this->_view->__get($member);
        }
    }
    
    public function _($phrase, array $data=null, $plural=null, $locale=null) {
        return $this->_context->_($phrase, $data, $plural, $locale);
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'path' => $this->_path,
            'args' => count($this->_args).' objects',
            'context' => $this->_context,
            'view' => $this->_view
        ];
    }
}
