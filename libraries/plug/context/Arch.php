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


// Request
    public function newRequest($request) {
        return archLib\Request::factory($request);
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


// Navigation
    public function getMenu($id) {
        return archLib\navigation\menu\Base::factory($this->_context, $id);
    }

    public function getBreadcrumbs($empty=false) {
        $application = $this->_context->getApplication();

        if(!$output = $application->_getCacheObject('breadcrumbs')) {
            if($empty) {
                $output = new archLib\navigation\breadcrumbs\EntryList();
            } else {
                $output = archLib\navigation\breadcrumbs\EntryList::generateFromRequest($this->_context->getDispatchRequest());
            }
            
            $application->_setCacheObject($output);
        }

        return $output;
    }

    public function clearMenuCache($id=null) {
        if($id !== null) {
            archLib\navigation\menu\Base::clearCacheFor($this->_context, $id);
        } else {
            archLib\navigation\menu\Base::clearCache($this->_context);
        }

        return $this;
    }
}
