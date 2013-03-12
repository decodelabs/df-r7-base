<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\context;

use df;
use df\core;
use df\arch;
use df\halo;

class Directory implements arch\IContextHelper {
    
    use arch\TContextHelper;

// Request
    public function newRequest($request) {
        return arch\Request::factory($request);
    }

    public function normalizeRequest($request, $from=null, $to=null) {
        if(!$request instanceof arch\IRequest) {
            $request = arch\Request::factory($request);
        } else {
            $request = clone $request;
        }
        
        
        if($from !== null) {
            if($from === true) {
                $from = $this->_context->request;
            }
            
            $request->setRedirectFrom($from);
        }
        
        if($to !== null) {
            if($to === true) {
                $to = $this->_context->request;
            }
            
            $request->setRedirectTo($to);
        }

        return $request;
    }

    public function backRequest($default=null, $success=true) {
        $request = $this->_context->request;
        
        if($success && ($redirect = $request->getRedirectTo())) {
            return $redirect;
        } else if((!$success || ($success && !$request->getRedirectTo()))
            && ($redirect = $request->getRedirectFrom())) {
            return $redirect;
        }
            
        if($default === null) {
            $default = $request->getParent();
        } else if(!$default instanceof arch\IRequest) {
            $default = $this->newRequest($default);
        } else {
            $default = clone $default;
        }

        return $default;
    }


// Actions
    public function actionExists($request, $runMode=null) {
        $request = arch\Request::factory($request);

        if($runMode === null) {
            $runMode = $this->_context->getRunMode();
        }

        $actionClass = arch\Action::getClassFor($request, $runMode);

        if(class_exists($actionClass)) {
            return true;
        }

        $controllerClass = arch\Controller::getClassFor($request, $runMode);

        if(!class_exists($controllerClass)) {
            return false;
        }

        return (bool)arch\Action::getControllerMethodName(
            $controllerClass, 
            arch\Context::factory($this->_context->getApplication(), $request)
        );
    }
    
    public function getAction($name, $context=true, arch\IController $controller=null, $runMode=null) {
        if($context === true) {
            $context = $this->_context;
        }
        
        if($context instanceof arch\IContext) {
            $request = clone $context->location;
        } else {
            $request = arch\Request::factory($context);
        }
        
        $request->setAction($name);
        
        if($runMode === null) {
            $runMode = $this->_context->getRunMode();
        }
        
        $context = arch\Context::factory($this->_context->getApplication(), $request);
        return arch\Action::factory($context, $controller);
    }
    
    public function controllerExists($request, $runMode=null) {
        $request = arch\Request::factory($request);

        if($runMode === null) {
            $runMode = $this->_context->getRunMode();
        }

        $class = arch\Controller::getClassFor($request, $runMode);

        return class_exists($class);
    }

    public function getController($request) {
        return arch\Controller::factory(
            arch\Context::factory(
                $this->_context->getApplication(), $request
            )
        );
    }
    
    public function getComponent($name, $context=true) {
        if($context === true) {
            $context = $this->_context;
        }
        
        if($context instanceof arch\IContext) {
            $request = clone $context->location;
        } else {
            $request = arch\Request::factory($context);
        }
        
        $context = arch\Context::factory($this->_context->getApplication(), $request);
        return arch\component\Base::factory($context, $name);
    }


// Facets
    public function newFacetController(Callable $initializer=null) {
        return new arch\FacetController($this->_context, $initializer);
    }
}
