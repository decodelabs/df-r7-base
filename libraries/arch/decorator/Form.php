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

abstract class Form implements IFormDecorator {

    use core\TContextAware;
    use core\lang\TChainable;
    use aura\view\TDeferredRenderable;
    use aura\view\TCascadingHelperProvider;

    public $values;
    public $content;
    public $form;

    public static function factory(arch\node\IFormNode $form) {
        $request = $form->context->location;
        $path = $request->getController();

        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = [];
        }

        $parts[] = '_decorators';
        $parts[] = ucfirst($request->getNode()).'Form';
        $end = implode('\\', $parts);

        $class = 'df\\apex\\directory\\'.$request->getArea().'\\'.$end;

        if(!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\'.$end;

            if(!class_exists($class)) {
                return null;
            }
        }

        return new $class($form);
    }

    protected function __construct(arch\node\IFormNode $form) {
        $this->context = $form->context;
        $this->form = $form;
        $this->view = &$form->view;
        $this->values = &$form->values;
        $this->content = &$form->content;

        $this->init();
    }

    protected function init() {}


    final public function renderUi() {
        $this->createUi();
        return $this;
    }

    abstract protected function createUi();


    final public function isRenderingInline() {
        return $this->form->isRenderingInline();
    }

    final public function getState() {
        return $this->form->getState();
    }

    final public function loadDelegate($id, $path) {
        return $this->form->loadDelegate($id, $path);
    }

    final public function directLoadDelegate($id, $class) {
        return $this->form->directLoadDelegate($id, $class);
    }

    final public function getDelegate($id) {
        return $this->form->getDelegate($id);
    }

    final public function hasDelegate($id) {
        return $this->form->hasDelegate($id);
    }

    final public function unloadDelegate($id) {
        $this->form->unloadDelegate($id);
        return $this;
    }

// Helpers
    final public function isValid() {
        return $this->form->isValid();
    }

    final public function countErrors() {
        return $this->form->countErrors();
    }

    final public function fieldName($name) {
        return $this->form->fieldName($name);
    }

    final public function eventName($name, ...$args) {
        return $this->form->eventName($name, ...$args);
    }

    final public function elementId($name) {
        return $this->form->elementId($name);
    }

// Store
    final public function setStore($key, $value) {
        $this->form->setStore($key, $value);
        return $this;
    }

    final public function hasStore($key) {
        return $this->form->hasStore($key);
    }

    final public function getStore($key, $default=null) {
        return $this->form->getStore($key, $default);
    }

    final public function removeStore($key) {
        $this->form->removeStore($key);
        return $this;
    }

    final public function clearStore() {
        $this->form->clearStore();
        return $this;
    }

// ArrayAccess
    final public function offsetSet($key, $value) {
        $this->form->offsetSet($key, $value);
        return $this;
    }

    final public function offsetGet($key) {
        return $this->form->offsetGet($key);
    }

    final public function offsetExists($key) {
        return $this->form->offsetExists($key);
    }

    final public function offsetUnset($key) {
        $this->form->offsetUnset($key);
        return $this;
    }
}