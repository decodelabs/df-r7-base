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


// Options
    public function fromForm(arch\node\IForm $form, string $name=null) {
        if($name === null) {
            $name = $this->_name;
        }

        return $this->setDelegate($form->getDelegate($name));
    }

    public function setDelegate(arch\node\IDelegate $delegate) {
        if(!$delegate instanceof arch\node\IResultProviderDelegate) {
            throw core\Error::EArgument(
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

    public function getDelegate(): ?arch\node\IDelegate {
        return $this->_delegate;
    }

    public function isRequired(bool $flag=null) {
        if($flag !== null) {
            $this->_isRequired = $flag;

            if($this->_delegate) {
                $this->_delegate->isRequired($this->_isRequired);
            }

            return $this;
        }

        return $this->_isRequired;
    }



// Validate
    public function validate() {
        // Prepare
        if(!$this->_delegate) {
            throw core\Error::ESetup('Delegate not set');
        }

        $value = false;

        $reqVal = $this->_isRequired;
        $this->isRequired($isRequired = $this->_isRequiredAfterToggle($value));
        $clear = $value === null;

        $value = $this->_delegate->apply();



        // Sanitize
        if($clear) {
            $value = null;
        }

        $value = $this->_sanitizeValue($value);
        $this->isRequired($reqVal);


        // Finalize
        if($this->_requireGroup !== null) {
            if($value === null || !$this->_delegate->isValid()) {
                if(!$this->validator->checkRequireGroup($this->_requireGroup)) {
                    $this->validator->setRequireGroupUnfulfilled($this->_requireGroup, $this->_name);
                }
            } else {
                $this->validator->setRequireGroupFulfilled($this->_requireGroup);
            }
        }

        return $value;
    }


// Apply
    public function applyValueTo(&$record, $value) {
        if($value === null && $this->isRequired()) {
            return $this;
        }

        return parent::applyValueTo($record, $value);
    }
}
