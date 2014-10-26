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
use df\link;

abstract class Base implements arch\IComponent {
    
    use core\TContextAware;
    use core\lang\TChainable;
    use user\TAccessLock;
    use core\TStringProvider;
    use aura\view\TDeferredRenderable;
    use aura\view\TCascadingHelperProvider;

    const DEFAULT_ACCESS = arch\IAccess::ALL;

    protected $_componentArgs = [];

    public static function factory(arch\IContext $context, $name, array $args=null) {
        $path = $context->location->getController();
        $area = $context->location->getArea();

        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = [];
        }
        
        $type = $context->getRunMode();
        
        $parts[] = '_components';
        $nameParts = explode('/', $name);
        $topName = array_pop($nameParts);

        if(!empty($nameParts)) {
            $parts = array_merge($parts, $nameParts);
        }

        $parts[] = ucfirst($topName);
        $class = 'df\\apex\\directory\\'.$area.'\\'.implode('\\', $parts);
        
        if(!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\'.implode('\\', $parts);

            if(!class_exists($class)) {
                try {
                    $scaffold = arch\scaffold\Base::factory($context);
                    return $scaffold->loadComponent($name, $args);
                } catch(arch\scaffold\IException $e) {}

                throw new arch\RuntimeException(
                    'Component ~'.$area.'/'.$path.'/#/'.$name.' could not be found'
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
            $args = [];
        }

        $this->_componentArgs = $args;

        if(method_exists($this, '_init')) {
            call_user_func_array([$this, '_init'], $args);
        }
    }

    public function getName() {
        $path = str_replace('\\', '/', get_class($this));
        $parts = explode('_components/', $path, 2);
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
        
        $output = call_user_func_array([$this, '_execute'], $this->_componentArgs);

        if($output instanceof aura\view\IDeferredRenderable) {
            $output->setRenderTarget($this->_renderTarget);
        }

        return $output;
    }

    public function toResponse() {
        try {
            $this->view = $this->getRenderTarget()->getView();
        } catch(\Exception $e) {
            $this->view = $this->_context->aura->getWidgetContainer()->getView();
        }

        if(!method_exists($this, '_execute')) {
            throw new arch\LogicException(
                'Component requires an _execute method'
            );
        }
        
        $output = call_user_func_array([$this, '_execute'], $this->_componentArgs);

        if($this->view && $output instanceof aura\view\IDeferredRenderable) {
            $output->setRenderTarget($this->getRenderTarget());
        }

        if($output instanceof link\http\IResponse) {
            return $output;
        }

        return $this->view;
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
