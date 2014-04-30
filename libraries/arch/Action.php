<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch;

use df;
use df\core;
use df\arch;

abstract class Action implements IAction, core\IDumpable {
    
    use core\TContextProxy;
    use TDirectoryAccessLock;
    use TResponseForcer;
    
    const CHECK_ACCESS = true;
    const DEFAULT_ACCESS = null;
    
    public $controller;
    
    public static function factory(IContext $context, IController $controller=null) {
        $class = self::getClassFor(
            $context->location,
            $context->getRunMode()
        );

        if(!$class) {
            throw new RuntimeException(
                'No action could be found for '.$context->location->toString(),
                404
            );
        }
        
        return new $class($context, $controller);
    }

    public static function getClassFor(IRequest $request, $runMode='Http') {
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

        if(!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\'.$end;

            if(!class_exists($class)) {
                array_pop($parts);
                $parts[] = $runMode.'Default';
                $end = implode('\\', $parts);

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
    
    
    public function __construct(IContext $context, IController $controller=null) {
        $this->controller = $controller;
        $this->_context = $context;

        if(!$this->controller) {
            $this->controller = Controller::factory($this->_context);
        }
    }
    
    public function getController() {
        return $this->controller;
    }


// Dispatch
    public function dispatch() {
        $output = null;
        $func = null;
        
        if(static::CHECK_ACCESS) {
            $client = $this->_context->getUserManager()->getClient();

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
            if(null !== ($output = $this->_dispatchRootDefaultAction())) {
                return $output;
            }

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

    protected function _dispatchRootDefaultAction() {
        $class = 'df\\apex\\directory\\'.$this->_context->location->getArea().'\\_actions\\HttpDefault';

        if(!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\_actions\\HttpDefault';

            if(!class_exists($class)) {
                $class = null;
            }
        }

        if($class && get_class($this) != $class) {
            $defaultAction = new $class($this->_context);
            return $defaultAction->dispatch();
        }
    }
    
    public function getActionMethodName() {
        $type = $this->_context->location->getType();
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
            'type' => $this->_context->getRunMode(),
            'controller' => $this->controller,
            'context' => $this->_context
        ];
    }
}