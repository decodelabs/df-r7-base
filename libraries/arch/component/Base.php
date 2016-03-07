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
    use aura\view\TSlotContainer;

    const DEFAULT_ACCESS = arch\IAccess::ALL;

    public $slots = [];
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
            try {
                $scaffold = arch\scaffold\Base::factory($context);
                return $scaffold->loadComponent($name, $args);
            } catch(arch\scaffold\IException $e) {}

            $class = 'df\\apex\\directory\\shared\\'.implode('\\', $parts);

            if(!class_exists($class)) {
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
        $this->context = $context;

        if(empty($args)) {
            $args = [];
        }

        $this->_componentArgs = $args;

        if(method_exists($this, 'init')) {
            $this->init(...$args);
        }
    }

    public function getName() {
        $path = str_replace('\\', '/', get_class($this));
        $parts = explode('_components/', $path, 2);
        return array_pop($parts);
    }


// Renderable
    public function toString() {
        return $this->render();
    }

    public function render() {
        $this->view = $this->getRenderTarget()->getView();

        if(!method_exists($this, '_execute')) {
            throw new arch\LogicException(
                'Component requires an _execute method'
            );
        }

        $output = $this->_execute(...$this->_componentArgs);

        if($output instanceof aura\view\IDeferredRenderable) {
            $output->setRenderTarget($this->_renderTarget);
        }

        return $output;
    }

    public function toResponse() {
        try {
            $this->view = $this->getRenderTarget()->getView();
        } catch(\Exception $e) {
            $this->view = $this->context->apex->newWidgetView();
        }

        if(!method_exists($this, '_execute')) {
            throw new arch\LogicException(
                'Component requires an _execute method'
            );
        }

        $output = $this->_execute(...$this->_componentArgs);

        if($this->view && $output instanceof aura\view\IDeferredRenderable) {
            $output->setRenderTarget($this->getRenderTarget());
        }

        if($output instanceof link\http\IResponse) {
            return $output;
        }

        return $this->view;
    }


// Slots
    public function getSlots() {
        return $this->slots;
    }

    public function clearSlots() {
        $this->slots = [];
        return $this;
    }

    public function setSlot(string $key, $value) {
        $this->slots[$key] = $value;
        return $this;
    }

    public function hasSlot(string $key) {
        return isset($this->slots[$key]);
    }

    public function slotExists(string $key) {
        return array_key_exists($key, $this->slots);
    }

    public function getSlot(string $key, $default=null) {
        if(isset($this->slots[$key])) {
            return $this->slots[$key];
        } else {
            return $default;
        }
    }

    public function removeSlot(string $key) {
        unset($this->slots[$key]);
        return $this;
    }

    public function esc($value) {
        return $this->html->esc($value);
    }

    public function offsetSet($key, $value) {
        return $this->setSlot($key, $value);
    }

    public function offsetGet($key) {
        return $this->getSlot($key);
    }

    public function offsetExists($key) {
        return $this->hasSlot($key);
    }

    public function offsetUnset($key) {
        return $this->removeSlot($key);
    }



// Access
    public function getAccessLockDomain() {
        return 'directory';
    }

    public function lookupAccessKey(array $keys, $action=null) {
        return $this->context->location->lookupAccessKey($keys, $action);
    }

    public function getDefaultAccess($action=null) {
        return static::DEFAULT_ACCESS;
    }

    public function getAccessLockId() {
        return $this->context->location->getAccessLockId();
    }
}
