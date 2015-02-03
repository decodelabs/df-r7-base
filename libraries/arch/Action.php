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
    
    use core\TContextProxy;
    use TDirectoryAccessLock;
    use TResponseForcer;
    
    const CHECK_ACCESS = null;
    const OPTIMIZE = false;
    const DEFAULT_ACCESS = null;
    
    private $_shouldOptimize = null;
    private $_shouldCheckAccess = null;
    private $_defaultAccess = null;
    private $_callback;
    
    public static function factory(IContext $context) {
        $class = self::getClassFor(
            $context->location,
            $context->getRunMode(),
            $isDefault
        );

        if(!$class || $isDefault) {
            try {
                $scaffold = arch\scaffold\Base::factory($context);
                return $scaffold->loadAction();
            } catch(arch\scaffold\IException $e) {}
        }

        if(!$class) {
            if($action = arch\Transformer::loadAction($context)) {
                return $action;
            }

            throw new RuntimeException(
                'No action could be found for '.$context->location->toString(),
                404
            );
        }
        
        return new $class($context);
    }

    public static function getClassFor(IRequest $request, $runMode='Http', &$isDefault=null) {
        $runMode = ucfirst($runMode);
        $path = $request->getController();
        
        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = [];
        }
        
        $parts[] = '_actions';
        $parts[] = $runMode.ucfirst($request->getAction());
        $end = implode('\\', $parts);
        
        $class = 'df\\apex\\directory\\'.$request->getArea().'\\'.$end;
        $isDefault = false;

        if(!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\'.$end;

            if(!class_exists($class)) {
                array_pop($parts);
                $parts[] = $runMode.'Default';
                $end = implode('\\', $parts);
                $isDefault = true;

                $class = 'df\\apex\\directory\\'.$request->getArea().'\\'.$end;

                if(!class_exists($class)) {
                    $class = 'df\\apex\\directory\\shared\\'.$end;

                    if(!class_exists($class)) {
                        $class = null;
                    }
                }
            }
        }

        return $class;
    }
    
    public function __construct(IContext $context, $callback=null) {
        $this->context = $context;
        $this->setCallback($callback);
    }

    public function setCallback($callback) {
        if($callback !== null) {
            $this->_callback = core\lang\Callback::factory($callback);
        } else {
            $this->_callback = null;
        }

        return $this;
    }

    public function getCallback() {
        return $this->_callback;
    }
    
    public function getController() {
        return $this->controller;
    }

    public function shouldOptimize($flag=null) {
        if($flag !== null) {
            $this->_shouldOptimize = (bool)$flag;
            return $this;
        }

        if($this->_shouldOptimize !== null) {
            return (bool)$this->_shouldOptimize;
        }

        return (bool)static::OPTIMIZE;
    }

    public function shouldCheckAccess($flag=null) {
        if($flag !== null) {
            $this->_shouldCheckAccess = (bool)$flag;
            return $this;
        }

        if($this->_shouldCheckAccess !== null) {
            return (bool)$this->_shouldCheckAccess;
        }
        
        if(is_bool(static::CHECK_ACCESS)) {
            return static::CHECK_ACCESS;
        }

        if($this->_defaultAccess === IAccess::ALL) {
            return false;
        }

        return !$this->shouldOptimize();
    }

    public function setDefaultAccess($access) {
        $this->_defaultAccess = $access;
        return $this;
    }

    public function getDefaultAccess($action=null) {
        if($this->_defaultAccess !== null) {
            return $this->_defaultAccess;
        }

        return $this->_getClassDefaultAccess();
    }


// Dispatch
    public function dispatch() {
        $output = null;
        $func = null;
        
        if($this->shouldCheckAccess()) {
            $client = $this->context->user->getClient();

            if($client->isDeactivated()) {
                $this->throwError(403, 'Client deactivated');
            }
            
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
        }

        if($this->_callback) {
            $output = $this->_callback->invoke($this);
        } else {
            $func = $this->getActionMethodName();
            
            if($output === null && $func) {
                try {
                    $output = $this->$func();
                } catch(ForcedResponse $e) {
                    $output = $e->getResponse();
                } catch(\Exception $e) {
                    $output = $this->handleException($e);
                }
            }
            
            if($func === null) {
                throw new RuntimeException(
                    'No handler could be found for action: '.
                    $this->context->location->toString(),
                    404
                );
            }
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

    public function getActionMethodName() {
        $type = $this->context->location->getType();
        $func = 'executeAs'.$type;
        
        if(!method_exists($this, $func)) {
            $func = 'execute';
            
            if(!method_exists($this, $func)) {
                $func = null;
            }
        }
        
        return $func;
    }

    public function handleException(\Exception $e) {
        throw $e;
    }
    
    
// Dump
    public function getDumpProperties() {
        return [
            'type' => $this->context->getRunMode(),
            'controller' => $this->controller,
            'context' => $this->context
        ];
    }
}