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

abstract class FormUi extends arch\component\Base implements arch\form\IForm, aura\html\widget\IWidgetProxy {

    use core\lang\TChainable;

    public $values;
    public $content;
    public $form;

    public function __construct(arch\IContext $context, array $args=null) {
        if($args) {
            $form = array_shift($args);
        } else {
            $form = null;
        }

        if(!$form instanceof arch\form\IForm) {
            throw new arch\InvalidArgumentException(
                'First FormUI component argument must be its parent form'
            );
        }

        $this->form = $form;
        $this->values = &$form->values;
        $this->content = &$form->content;

        if($this->form->view) {
            $this->setRenderTarget($this->form->view);
        }

        parent::__construct($context, $args);
    }

    public function getForm() {
        return $this->form;
    }

    public function toWidget() {
        return $this->render();
    }

    public function isRenderingInline() {
        return $this->form->isRenderingInline();
    }

    public function getStateController() {
        return $this->form->getStateController();
    }

    public function loadDelegate($id, $path) {
        return $this->form->loadDelegate($id, $path);
    }

    public function directLoadDelegate($id, $class) {
        return $this->form->directLoadDelegate($id, $class);
    }

    public function getDelegate($id) {
        return $this->form->getDelegate($id);
    }

    public function hasDelegate($id) {
        return $this->form->hasDelegate($id);
    }

    public function unloadDelegate($id) {
        $this->form->unloadDelegate($id);
        return $this;
    }

// Helpers
    public function isValid() {
        return $this->form->isValid();
    }

    public function fieldName($name) {
        return $this->form->fieldName($name);
    }

    public function eventName($name) {
        return call_user_func_array([$this->form, 'eventName'], func_get_args());
    }

    public function elementId($name) {
        return $this->form->elementId($name);
    }

// Store
    public function setStore($key, $value) {
        $this->form->setStore($key, $value);
        return $this;
    }

    public function hasStore($key) {
        return $this->form->hasStore($key);
    }

    public function getStore($key, $default=null) {
        return $this->form->getStore($key, $default);
    }

    public function removeStore($key) {
        $this->form->removeStore($key);
        return $this;
    }

    public function clearStore() {
        $this->form->clearStore();
        return $this;
    }
}