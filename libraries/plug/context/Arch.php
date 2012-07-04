<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug\context;

use df;
use df\core;
use df\arch as archLib;
use df\halo;

class Arch implements archLib\IContextHelper {
    
    protected $_context;
    
    public function __construct(archLib\IContext $context) {
        $this->_context = $context;
    }
    
    public function getContext() {
        return $this->_context;
    }

    public function actionExists($request, $runMode=null) {
        $request = archLib\Request::factory($request);

        if($runMode === null) {
            $runMode = $this->_context->getRunMode();
        }

        $actionClass = archLib\Action::getClassFor($request, $runMode);

        if(class_exists($actionClass)) {
            return true;
        }

        $controllerClass = archLib\Controller::getClassFor($request, $runMode);

        if(!class_exists($controllerClass)) {
            return false;
        }

        return (bool)archLib\Action::getControllerMethodName(
            $controllerClass, 
            archLib\Context::factory($this->_context->getApplication(), $request)
        );
    }
    
    public function getAction($name, $context=true, archLib\IController $controller=null, $runMode=null) {
        if($context === true) {
            $context = $this->_context;
        }
        
        if($context instanceof archLib\IContext) {
            $request = clone $context->getRequest();
        } else {
            $request = archLib\Request::factory($context);
        }
        
        $request->setAction($name);
        
        if($runMode === null) {
            $runMode = $this->_context->getRunMode();
        }
        
        $context = archLib\Context::factory($this->_context->getApplication(), $request);
        return archLib\Action::factory($context, $controller);
    }
    
    public function controllerExists($request, $runMode=null) {
        $request = archLib\Request::factory($request);

        if($runMode === null) {
            $runMode = $this->_context->getRunMode();
        }

        $class = archLib\Controller::getClassFor($request, $runMode);

        return class_exists($class);
    }

    public function getController($request) {
        return archLib\Controller::factory(
            archLib\Context::factory(
                $this->_context->getApplication(), $request
            )
        );
    }
    
    public function getComponent($name, $context=true) {
        if($context === true) {
            $context = $this->_context;
        }
        
        if($context instanceof archLib\IContext) {
            $request = clone $context->getRequest();
        } else {
            $request = archLib\Request::factory($context);
        }
        
        $context = archLib\Context::factory($this->_context->getApplication(), $request);
        return archLib\Component::factory($context, $name);
    }
}
