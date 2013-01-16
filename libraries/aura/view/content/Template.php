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
    use aura\view\TDeferredRenderable;
        
    private $_path;
    private $_view;
    private $_isRendering = false;
    private $_isLayout = false;
    private $_innerContent = null;
    
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

    public static function loadThemeTemplate(aura\view\IView $view, $path, $themeId=null) {
        if($themeId === null) {
            $theme = $view->getTheme();
            $themeId = $theme->getId();
        }

        $context = $view->getContext();
        
        $lookupPaths = array();
        $area = $context->getRequest()->getArea();

        $lookupPaths[] = 'apex/themes/'.$themeId.'/templates/'.$area.'/'.$path.'.php';
        
        if($area !== 'shared') {
            $lookupPaths[] = 'apex/themes/'.$themeId.'/templates/shared/'.$path.'.php';
        }
        
        if($themeId !== 'shared') {
            $lookupPaths[] = 'apex/themes/shared/templates/'.$area.'/'.$path.'.php';
            
            if($area !== 'shared') {
                $lookupPaths[] = 'apex/themes/shared/templates/shared/'.$path.'.php';
            }
        }
        
        foreach($lookupPaths as $testPath) {
            if($templatePath = $context->findFile($testPath)) {
                break;
            }
        }
        
        if(!$templatePath) {
            throw new aura\view\ContentNotFoundException(
                'Theme template '.$path.' could not be found'
            );
        }
        
        return new self($context, $templatePath);
    }
    
    public static function loadLayout(aura\view\ILayoutView $view, $pathName=null, $type=null) {
        if($pathName === null) {
            $pathName = $view->getLayout();
        }

        if($type === null) {
            $type = lcfirst($view->getType());
        }

        $path = $pathName.'.'.$type;
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
        
        return new self($context, $layoutPath, true);
    }
    
    public function __construct(arch\IContext $context, $absolutePath, $isLayout=false) {
        if(!is_file($absolutePath)) {
            throw new ContentNotFoundException(
                'Template '.$absolutePath.' could not be found'
            );
        }
        
        $this->_path = $absolutePath;
        $this->_context = $context;
        $this->_isLayout = $isLayout;
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
    
    public function render() {
        if($this->_isRendering) {
            throw new aura\view\RuntimeException('Rendering is already in progress');
        }
        
        $target = $this->getRenderTarget();
        $this->_isRendering = true;
        $this->_view = $target->getView();
        
        if($this->_isLayout) {
            // Prepare inner template content before rendering to ensure 
            // sub templates can affect layout properties
            $this->renderInnerContent();
        }

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
    
    public function toResponse() {
        return $this->_view;
    }
    
    protected function renderInnerContent() {
        if(!$this->_isLayout || $this->_innerContent === false) {
            return null;
        }

        if($this->_innerContent !== null) {
            return $this->_innerContent;
        }

        $this->_innerContent = false;
        $provider = $this->getView()->getContentProvider();
        
        if($provider !== $this) {
            return $this->_innerContent = $provider->renderTo($this);
        }
    }
    
    public function isRendering() {
        return $this->_isRendering;
    }

    public function isLayout() {
        return $this->_isLayout;
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
