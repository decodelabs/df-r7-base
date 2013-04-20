<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df;
use df\core;
use df\arch;

class Action implements IAction, core\IDumpable {
    
    use TContextProxy;
    use TDirectoryAccessLock;
    
    const CHECK_ACCESS = true;
    const DEFAULT_ACCESS = null;
    
    private $_isInline = false;
    private $_controller;
    
    public static function factory(IContext $context, IController $controller=null) {
        $class = self::getClassFor(
            $context->location,
            $context->getRunMode()
        );
        
        if(!class_exists($class)) {
            $class = __CLASS__;
        }

        return new $class($context, $controller);
    }

    public static function getClassFor(IRequest $request, $runMode='Http') {
        $runMode = ucfirst($runMode);
        $path = $request->getController();
        
        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = array();
        }
        
        $parts[] = '_actions';
        $parts[] = $runMode.ucfirst($request->getAction());
        
        return 'df\\apex\\directory\\'.$request->getArea().'\\'.implode('\\', $parts);
    }
    
    
    public function __construct(IContext $context, IController $controller=null) {
        $this->_controller = $controller;
        $this->_context = $context;
        $this->_isInline = get_class($this) == __CLASS__;
    }
    
    public function getController() {
        if(!$this->_controller) {
            $this->_controller = Controller::factory($this->_context);
        }
        
        return $this->_controller;
    }
    
    
// Dispatch
    public function dispatch() {
        $output = null;
        $func = null;
        
        if(!$this->_isInline) {
            if(static::CHECK_ACCESS) {
                $client = $this->_context->getUserManager()->getClient();
                
                if(!$client->canAccess($this)) {
                    $this->throwError(401, 'Insufficient permissions');
                }
            }
            
            if(method_exists($this, '_beforeDispatch')) {
                try {
                    $output = $this->_beforeDispatch();
                } catch(ForcedResponse $e) {
                    $output = $e->getResponse();
                }

                $func = false;
            }
            
            if($output === null && $func = $this->getActionMethodName($this, $this->_context)) {
                if($this->_controller) {
                    $this->_controller->setActiveAction($this);
                }
                
                try {
                    $output = $this->$func();
                } catch(ForcedResponse $e) {
                    $output = $e->getResponse();
                }
                
                if($this->_controller) {
                    $this->_controller->setActiveAction(null);
                }
            }
        }
        
        if($func === null) {
            $controller = $this->getController();
            
            if($func = $this->getControllerMethodName($this->_controller, $this->_context)) {
                if($controller::CHECK_ACCESS) {
                    $client = $this->_context->getUserManager()->getClient();
                
                    if(!$client->canAccess($this)) {
                        $this->throwError(401, 'Insufficient permissions');
                    }
                }
                
                $controller->setActiveAction($this);

                try {
                    $output = $controller->$func();
                } catch(ForcedResponse $e) {
                    $output = $e->getResponse();
                }

                $controller->setActiveAction(null);
            }
        }
        
        if($func === null) {
            throw new RuntimeException(
                'No handler could be found for action: '.
                $this->_context->location->toString(),
                404
            );
        }
        
        if(method_exists($this, '_afterDispatch')) {
            try {
                $output = $this->_afterDispatch($output);
            } catch(ForcedResponse $e) {
                $output = $e->getResponse();
            }
        }
        
        return $output;
    }
    
    public function forceResponse($response) {
        throw new ForcedResponse($response);
    }
    
    public static function getActionMethodName($actionClass, IContext $context) {
        $type = $context->location->getType();
        $func = 'executeAs'.$type;
        
        if(!method_exists($actionClass, $func)) {
            $func = 'execute';
            
            if(!method_exists($actionClass, $func)) {
                $func = null;
            }
        }
        
        return $func;
    }
    
    public static function getControllerMethodName($controllerClass, IContext $context) {
        $actionName = $context->location->getAction();
        
        if(is_numeric(substr($actionName, 0, 1))) {
            $actionName = '_'.$actionName;
        }
        
        $type = $context->location->getType();
        $func = $actionName.$type.'Action';
        
        if(!method_exists($controllerClass, $func)) {
            $func = $actionName.'Action';  
            
            if(!method_exists($controllerClass, $func)) {
                $func = 'default'.$type.'Action';
                
                if(!method_exists($controllerClass, $func)) {
                    $func = 'defaultAction';
                    
                    if(!method_exists($controllerClass, $func)) {
                        $func = null;
                    }    
                }
            }  
        }    
        
        return $func;
    }
    
    
    public function isActionInline() {
        return $this->_isInline;
    }
    
    
    
// Access
    public function getDefaultAccess($action=null) {
        if(!$this->_isInline) {
            return $this->_getClassDefaultAccess();
        }

        $controller = $this->getController();
        
        if($controller->isControllerInline()) {
            //return $this->_getClassDefaultAccess();
            return true;
        } else {
            return $controller->getDefaultAccess($action);
        }
    }

    
// Dump
    public function getDumpProperties() {
        $runMode = $this->_context->getRunMode();
        
        if($this->_isInline) {
            $runMode .= ' (inline)';
        }
        
        return [
            'type' => $runMode,
            'controller' => $this->_controller,
            'context' => $this->_context
        ];
    }
}