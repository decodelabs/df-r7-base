<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form;

use df;
use df\core;
use df\arch;
use df\aura;
use df\flex;

class Delegate implements IDelegate {

    use core\TContextAware;
    use TForm;

    const DEFAULT_REDIRECT = null;

    protected $_delegateId;
    private $_isNew = false;
    private $_isComplete = false;

    public function __construct(arch\IContext $context, IStateController $state, $id) {
        $this->context = $context;
        $this->_state = $state;
        $this->_delegateId = $id;

        $this->values = $state->getValues();
        $this->afterConstruct();
    }

    protected function afterConstruct() {}

    public function getDelegateId() {
        return $this->_delegateId;
    }

    public function getDelegateKey() {
        $parts = explode('.', $this->_delegateId);
        return array_pop($parts);
    }

    final public function beginInitialize() {
        $this->init();
        $this->loadDelegates();

        if($this->_state->isNew()) {
            $this->_isNew = true;
            $this->setDefaultValues();
        }

        foreach($this->_delegates as $delegate) {
            $delegate->beginInitialize();
        }

        return $this;
    }

    final public function endInitialize() {
        foreach($this->_delegates as $delegate) {
            $delegate->endInitialize();
        }

        if($this instanceof IDependentDelegate) {
            $this->normalizeDependencyValues();
        }

        $this->_state->isNew(false);
        $this->afterInit();

        return $this;
    }

    public function isNew() {
        return $this->_isNew;
    }

    public function setRenderContext(aura\view\IView $view, aura\view\IContentProvider $content, $isRenderingInline=false) {
        $this->view = $view;
        $this->content = $content;
        $this->_isRenderingInline = $isRenderingInline;

        foreach($this->_delegates as $delegate) {
            $delegate->setRenderContext($view, $content);
        }

        return $this;
    }


    public function setComplete($success=true) {
        $this->_isComplete = true;
        $this->_onComplete($success);

        foreach($this->_delegates as $delegate) {
            $delegate->setComplete($success);
        }

        $this->_state->reset();
        return $this;
    }

    public function isComplete() {
        return $this->_isComplete;
    }

    protected function _onComplete($success) {}


    protected function getDefaultRedirect() {
        return static::DEFAULT_REDIRECT;
    }


// State
    public function reset() {
        $this->_state->reset();

        foreach($this->_delegates as $id => $delegate) {
            $this->unloadDelegate($id);
        }

        $this->afterReset();

        $this->loadDelegates();
        $this->setDefaultValues();

        foreach($this->_delegates as $id => $delegate) {
            $delegate->initialize();
        }

        $this->_state->isNew(false);
        $this->afterInit();

        return $this;
    }

    protected function afterReset() {}



// Events
    protected function onCancelEvent() {
        $this->setComplete(false);
        return $this->_getCompleteRedirect();
    }


// Names
    public function fieldName($name) {
        $parts = explode('[', $name, 2);
        $parts[0] .= ']';

        return '_delegates['.$this->_delegateId.']['.implode('[', $parts);
    }

    public function elementId($name) {
        return flex\Text::formatSlug($this->getDelegateId().'-'.$name);
    }



    public function getStateData() {
        $output = [
            'isValid' => $this->isValid(),
            'isNew' => $this->_isNew,
            'values' => $this->values->toArrayDelimitedSet('_delegates['.$this->_delegateId.']'),
            'errors' => []
        ];

        foreach($this->_delegates as $delegate) {
            $delegateState = $delegate->getStateData();

            if(!$delegateState['isValid']) {
                $output['isValid'] = false;
            }

            $output['values'] = array_merge($output['values'], $delegateState['values']);
            $output['errors'] = array_merge($output['errors'], $delegateState['errors']);
        }

        return $output;
    }
}