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
    
    use archLib\TContextHelper;

// Request
    public function newRequest($request) {
        return archLib\Request::factory($request);
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
        } else if(!$default instanceof archLib\IRequest) {
            $default = $this->newRequest($default);
        } else {
            $default = clone $default;
        }

        return $default;
    }


// Actions
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
            $request = clone $context->location;
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
            $request = clone $context->location;
        } else {
            $request = archLib\Request::factory($context);
        }
        
        $context = archLib\Context::factory($this->_context->getApplication(), $request);
        return archLib\Component::factory($context, $name);
    }


// Facets
    public function newFacetController(Callable $initializer=null) {
        return new archLib\FacetController($this->_context, $initializer);
    }



// Notifications
    public function getNotificationManager() {
        return archLib\notify\Manager::getInstance($this->_context->getApplication());
    }

    public function notify($id, $message=null, $type=null) {
        $manager = $this->getNotificationManager();
        $message = $manager->newMessage($id, $message, $type);
        $manager->queueMessage($message);

        return $message;
    }

    public function notifyNow($id, $message=null, $type=null) {
        $manager = $this->getNotificationManager();
        $message = $manager->newMessage($id, $message, $type);
        $manager->setInstantMessage($message);

        return $message;
    }

    public function notifyAlways($id, $message=null, $type=null) {
        $manager = $this->getNotificationManager();
        $message = $manager->newMessage($id, $message, $type);
        $manager->setConstantMessage($message);

        return $message;
    }

    public function removeConstantNotification($id) {
        $manager = $this->getNotificationManager();
        $manager->removeConstantMessage($id);

        return $this;
    }
}
