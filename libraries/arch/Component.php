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
use df\aura;

abstract class Component implements IComponent {
    
    use TContextProxy;
    use user\TAccessLock;
    use core\TStringProvider;
    use aura\view\TDeferredRenderable;

    const DEFAULT_ACCESS = arch\IAccess::ALL;

    public $view;
    public $html;
    
    public static function factory(IContext $context, $name, array $args=null) {
        $request = $context->getRequest();
        $path = $request->getController();
        
        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = array();
        }
        
        $type = $context->getRunMode();
        
        $parts[] = '_components';
        $parts[] = ucfirst($name);
        
        $class = 'df\\apex\\directory\\'.$request->getArea().'\\'.implode('\\', $parts);
        
        if(!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\'.implode('\\', $parts);

            if(!class_exists($class)) {
                throw new RuntimeException(
                    'Component ~'.$request->getArea().'/'.$path.'/'.ucfirst($name).' could not be found'
                );
            }
        }
        
        return new $class($context, $args);
    }
    
    public function __construct(arch\IContext $context, array $args=null) {
        $this->_context = $context;

        if(empty($args)) {
            $args = array();
        }

        if(method_exists($this, '_init')) {
            call_user_func_array([$this, '_init'], $args);
        }
    }

    public function getName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }
    
    
// Renderable
    public function toString() {
        try {
            return (string)$this->render();
        } catch(\Exception $e) {
            if($this->_renderTarget) {
                return $this->_renderTarget->getView()->newErrorContainer($e);
            } else {
                return 'ERROR: '.$e->getMessage();
            }
        }
    }

    public function render() {
        $this->view = $this->getRenderTarget()->getView();
        $this->html = $this->view->html;
        
        $output = $this->_execute();

        if($output instanceof aura\view\IDeferredRenderable) {
            $output->setRenderTarget($this->_renderTarget);
        }

        $this->view = null;
        $this->html = null;

        return $output;
    }

    abstract protected function _execute();


// Access
    public function getAccessLockDomain() {
        return 'directory';
    }
    
    public function lookupAccessKey(array $keys, $action=null) {
        return $this->_context->getRequest()->lookupAccessKey($keys, $action);
    }
    
    public function getDefaultAccess($action=null) {
        return static::DEFAULT_ACCESS;
    }

    public function getAccessLockId() {
        return $this->_context->getRequest()->getAccessLockId();
    }
}
