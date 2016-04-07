<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\decorator;

use df;
use df\core;
use df\arch;
use df\aura;

abstract class Delegate implements IDelegateDecorator {

    use core\TContextAware;
    use core\lang\TChainable;
    use aura\view\TView_DeferredRenderable;
    use aura\view\TView_CascadingHelperProvider;

    public $values;
    public $content;
    public $delegate;

    public static function factory(arch\node\IDelegate $delegate) {
        $request = $delegate->context->location;
        $path = $request->getController();

        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = [];
        }

        $dClass = explode('\\', get_class($delegate));
        $name = array_pop($dClass);

        $parts[] = '_decorators';
        $parts[] = $name.'Delegate';
        $end = implode('\\', $parts);

        $class = 'df\\apex\\directory\\'.$request->getArea().'\\'.$end;

        if(!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\'.$end;

            if(!class_exists($class)) {
                return null;
            }
        }

        return new $class($delegate);
    }

    protected function __construct(arch\node\IDelegate $delegate) {
        $this->context = $delegate->context;
        $this->delegate = $delegate;
        $this->view = &$delegate->view;
        $this->values = &$delegate->values;
        $this->content = &$delegate->content;

        $this->init();
    }


    protected function init() {}

    final public function renderUi() {
        $this->createUi();
        return $this;
    }

    protected function createUi() {
        // add default message?
    }


    final public function isRenderingInline() {
        return $this->delegate->isRenderingInline();
    }

    final public function getState() {
        return $this->delegate->getState();
    }

    final public function loadDelegate($id, $path) {
        return $this->delegate->loadDelegate($id, $path);
    }

    final public function directLoadDelegate($id, $class) {
        return $this->delegate->directLoadDelegate($id, $class);
    }

    final public function getDelegate($id) {
        return $this->delegate->getDelegate($id);
    }

    final public function hasDelegate($id) {
        return $this->delegate->hasDelegate($id);
    }

    final public function unloadDelegate($id) {
        $this->delegate->unloadDelegate($id);
        return $this;
    }

// Helpers
    final public function isValid() {
        return $this->delegate->isValid();
    }

    final public function countErrors() {
        return $this->delegate->countErrors();
    }

    final public function fieldName($name) {
        return $this->delegate->fieldName($name);
    }

    final public function eventName($name, ...$args) {
        return $this->delegate->eventName($name, ...$args);
    }

    final public function elementId($name) {
        return $this->delegate->elementId($name);
    }

// Store
    final public function setStore($key, $value) {
        $this->delegate->setStore($key, $value);
        return $this;
    }

    final public function hasStore(...$keys): bool {
        return $this->delegate->hasStore(...$keys);
    }

    final public function getStore($key, $default=null) {
        return $this->delegate->getStore($key, $default);
    }

    final public function removeStore(...$keys) {
        $this->delegate->removeStore(...$keys);
        return $this;
    }

    final public function clearStore() {
        $this->delegate->clearStore();
        return $this;
    }

// ArrayAccess
    final public function offsetSet($key, $value) {
        $this->delegate->offsetSet($key, $value);
        return $this;
    }

    final public function offsetGet($key) {
        return $this->delegate->offsetGet($key);
    }

    final public function offsetExists($key) {
        return $this->delegate->offsetExists($key);
    }

    final public function offsetUnset($key) {
        $this->delegate->offsetUnset($key);
        return $this;
    }
}