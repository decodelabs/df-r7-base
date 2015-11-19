<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\arch;

class Delegate extends Base implements core\validate\IDelegateField {

    protected $_delegate;
    protected $_isRequired = null;

    public function fromForm(arch\action\IForm $form, $name=null) {
        if($name === null) {
            $name = $this->_name;
        }

        return $this->setDelegate($form->getDelegate($name));
    }

    public function setDelegate(arch\action\IDelegate $delegate) {
        if(!$delegate instanceof arch\action\IResultProviderDelegate) {
            throw new core\validate\InvalidArgumentException(
                'Delegate '.$delegate->getDelegateId().' does not provide a result'
            );
        }

        $this->_delegate = $delegate;

        if($this->_isRequired !== null) {
            $delegate->isRequired($this->_isRequired);
        } else {
            $this->_isRequired = $delegate->isRequired();
        }

        return $this;
    }

    public function getDelegate() {
        return $this->_delegate;
    }

    public function isRequired($flag=null) {
        if($flag !== null) {
            $this->_isRequired = (bool)$flag;

            if($this->_delegate) {
                $this->_delegate->isRequired($this->_isRequired);
            }

            return $this;
        }

        return $this->_isRequired;
    }

    public function validate(core\collection\IInputTree $node) {
        $value = $this->_delegate->apply();
        $value = $this->_sanitizeValue($value);

        if(!$this->_delegate->isValid()) {
            $node->addError('delegate', 'Delegate did not complete');

            if($this->_requireGroup !== null && !$this->validator->checkRequireGroup($this->_requireGroup)) {
                $this->validator->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
            }
        } else {
            if($this->_requireGroup !== null) {
                $this->validator->setRequireGroupFulfilled($this->_requireGroup);
            }
        }

        return $value;
    }
}