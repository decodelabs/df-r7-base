<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\component\template;

use df;
use df\core;
use df\arch;
use df\aura;
    
abstract class FormUi extends arch\component\Base implements arch\form\IForm, aura\html\widget\IWidgetProxy, core\IArgContainer, \ArrayAccess {

    use core\TArrayAccessedArgContainer;

    public $values;
    public $content;

    protected $_form;

    protected function _init(arch\form\IForm $form) {
        $this->_form = $form;
        $this->values = &$form->values;
        $this->content = &$form->content;
    }

    public function getForm() {
        return $this->_form;
    }

    public function toWidget() {
        return $this->render();
    }

    public function isRenderingInline() {
        return $this->_form->isRenderingInline();
    }

    public function getStateController() {
        return $this->_form->getStateController();
    }

    public function loadDelegate($id, $name, $request=null) {
        return $this->_form->loadDelegate($id, $name, $request=null);
    }

    public function getDelegate($id) {
        return $this->_form->getDelegate($id);
    }

    public function handleEvent($name, array $args=array()) {
        core\stub($name, $args);
    }

// Helpers
    public function isValid() {
        return $this->_form->isValid();
    }

    public function fieldName($name) {
        return $this->_form->fieldName($name);
    }

    public function eventName($name) {
        return call_user_func_array([$this->_form, 'eventName'], func_get_args());
    }

    public function elementId($name) {
        return $this->_form->elementId($name);
    }

// Store
    public function setStore($key, $value) {
        $this->_form->setStore($key, $value);
        return $this;
    }

    public function getStore($key, $default=null) {
        return $this->_form->getStore($key, $default);
    }

    public function removeStore($key) {
        $this->_form->removeStore($key);
        return $this;
    }

    public function clearStore() {
        $this->_form->clearStore();
        return $this;
    }
}