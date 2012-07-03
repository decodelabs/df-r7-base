<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df;
use df\core;
use df\arch;
use df\user;

class Action implements IAction, core\IDumpable {
    
    use TContextProxy;
    use TDirectoryAccessLock;
    
    const CHECK_ACCESS = true;
    const DEFAULT_ACCESS = user\IState::NONE;
    
    private $_isInline = false;
    private $_controller;
    
    public static function factory(IContext $context, IController $controller=null) {
        $request = $context->getRequest();
        $path = $request->getController();
        
        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = array();
        }
        
        $type = $context->getRunMode();
        
        $parts[] = '_actions';
        $parts[] = $type.ucfirst($request->getAction());
        
        $class = 'df\\apex\\directory\\'.$request->getArea().'\\'.implode('\\', $parts);
        
        if(!class_exists($class)) {
            $class = __CLASS__;
        }

        return new $class($context, $controller);
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
                $output = $this->_beforeDispatch();
                $func = false;
            }
            
            if($output === null && $func = $this->_getActionMethod()) {
                if($this->_controller) {
                    $this->_controller->setActiveAction($this);
                }
                
                $output = $this->$func();
                
                if($this->_controller) {
                    $this->_controller->setActiveAction(null);
                }
            }
        }
        
        if($func === null) {
            $controller = $this->getController();
            
            if($func = $this->_getControllerMethod()) {
                if($controller::CHECK_ACCESS) {
                    $client = $this->_context->getUserManager()->getClient();
                
                    if(!$client->canAccess($this)) {
                        $this->throwError(401, 'Insufficient permissions');
                    }
                }
                
                $controller->setActiveAction($this);
                $output = $controller->$func();
                $controller->setActiveAction(null);
            }
        }
        
        if($func === null) {
            throw new RuntimeException(
                'No handler could be found for action: '.
                $this->_context->getRequest()->toString(),
                404
            );
        }
        
        if(method_exists($this, '_afterDispatch')) {
            $output = $this->_afterDispatch($output);
        }
        
        return $output;
    }
    
    protected function _getActionMethod() {
        $type = $this->_context->getRequest()->getType();
        $func = 'executeAs'.$type;
        
        if(!method_exists($this, $func)) {
            $func = 'execute';
            
            if(!method_exists($this, $func)) {
                $func = null;
            }
        }
        
        return $func;
    }
    
    protected function _getControllerMethod() {
        $actionName = $this->_context->getRequest()->getAction();
        
        if(is_numeric(substr($actionName, 0, 1))) {
            $actionName = '_'.$actionName;
        }
        
        $type = $this->_context->getRequest()->getType();
        $func = $actionName.$type.'Action';
        
        if(!method_exists($this->_controller, $func)) {
            $func = $actionName.'Action';  
            
            if(!method_exists($this->_controller, $func)) {
                $func = 'default'.$type.'Action';
                
                if(!method_exists($this->_controller, $func)) {
                    $func = 'defaultAction';
                    
                    if(!method_exists($this->_controller, $func)) {
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
    public function getDefaultAccess() {
        if(!$this->_isInline) {
            return static::DEFAULT_ACCESS;
        }
        
        $controller = $this->getController();
        
        if($controller->isControllerInline()) {
            return static::DEFAULT_ACCESS;
        } else {
            return $controller->getDefaultAccess();
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