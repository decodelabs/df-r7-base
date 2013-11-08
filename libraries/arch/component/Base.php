<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\component;

use df;
use df\core;
use df\arch;
use df\user;
use df\aura;

abstract class Base implements arch\IComponent {
    
    use core\TContextAware;
    use user\TAccessLock;
    use core\TStringProvider;
    use aura\view\TDeferredRenderable;
    use aura\view\TCascadingHelperProvider;

    const DEFAULT_ACCESS = arch\IAccess::ALL;

    private $_args = [];

    public static function factory(arch\IContext $context, $name, array $args=null) {
        $path = $context->location->getController();
        $area = $context->location->getArea();

        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = array();
        }
        
        $type = $context->getRunMode();
        
        $parts[] = '_components';
        $parts[] = ucfirst($name);
        
        $class = 'df\\apex\\directory\\'.$area.'\\'.implode('\\', $parts);
        
        if(!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\'.implode('\\', $parts);

            if(!class_exists($class)) {
                throw new arch\RuntimeException(
                    'Component ~'.$area.'/'.$path.'/'.ucfirst($name).' could not be found'
                );
            }
        }
        
        return new $class($context, $args);
    }

    public static function themeFactory(arch\IContext $context, $themeName, $name, array $args=null) {
        $class = 'df\\apex\\themes\\'.$themeName.'\\components\\'.ucfirst($name);

        if(!class_exists($class)) {
            $class = 'df\\apex\\themes\\shared\\components\\'.ucfirst($name);

            if(!class_exists($class)) {
                throw new arch\RuntimeException(
                    'Theme component '.ucfirst($name).' could not be found'
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

        $this->_args = $args;

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
            return $this->render();
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

        if(!method_exists($this, '_execute')) {
            throw new arch\LogicException(
                'Component requires an _execute method'
            );
        }
        
        $output = call_user_func_array([$this, '_execute'], $this->_args);

        if($output instanceof aura\view\IDeferredRenderable) {
            $output->setRenderTarget($this->_renderTarget);
        }

        return $output;
    }


// Access
    public function getAccessLockDomain() {
        return 'directory';
    }
    
    public function lookupAccessKey(array $keys, $action=null) {
        return $this->_context->location->lookupAccessKey($keys, $action);
    }
    
    public function getDefaultAccess($action=null) {
        return static::DEFAULT_ACCESS;
    }

    public function getAccessLockId() {
        return $this->_context->location->getAccessLockId();
    }
}
