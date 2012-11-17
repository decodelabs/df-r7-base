<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate;

use df;
use df\core;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}



// Interfaces
interface IHandler {
    public function addField($name, $type, array $options=null);
    public function getField($name);
    public function getFields();
    public function getValues();
    public function getValue($name);
    public function shouldSanitize($flag=null);
    
    public function isValid();
    public function validate(core\collection\IInputTree $data);
    public function applyTo(&$targetRecord);
}



interface IField {
    public function getHandler();
    public function getName();
    public function isRequired($flag=null);
    public function shouldSanitize($flag=null);
    public function setCustomValidator(Callable $validator);
    public function getCustomValidator();
    
    public function end();
    public function validate(core\collection\IInputTree $node);
    public function applyValueTo(&$record, $value);
}

interface IRangeField extends IField {
    public function setMin($min);
    public function getMin();
    public function setMax($max);
    public function getMax();
}

trait TRangeField {

    protected $_min;
    protected $_max;

    public function setMin($min) {
        if($min !== null) {
            $min = (float)$min;
        }
        
        $this->_min = $min;
        return $this;
    }
    
    public function getMin() {
        return $this->_min;
    }
    
    public function setMax($max) {
        if($max !== null) {
            $max = (float)$max;
        }
        
        $this->_max = $max;
        return $this;
    }
    
    public function getMax() {
        return $this->_max;
    }

    protected function _validateRange(core\collection\IInputTree $node, $value) {
        if($this->_min !== null && $value < $this->_min) {
            $node->addError('min', $this->_handler->_(
                'This field must be at least %min%',
                array('%min%' => $this->_min)
            ));
        }
        
        if($this->_max !== null && $value > $this->_max) {
            $node->addError('max', $this->_handler->_(
                'This field must not be more than %max%',
                array('%max%' => $this->_max)
            ));
        }
    }
}


interface IMinLengthField extends IField {
    public function setMinLength($length);
    public function getMinLength();
}

trait TMinLengthField {

    protected $_minLength = null;

    public function setMinLength($length) {
        if($length !== null) {
            $length = (int)$length;

            if(empty($length)) {
                $length = 0;
            }

            if($length < 0) {
                $length = 0;
            }
        }
        
        $this->_minLength = $length;
        return $this;
    }
    
    public function getMinLength() {
        return $this->_minLength;
    }

    protected function _setDefaultMinLength($length) {
        if($this->_minLength === null) {
            $this->_minLength = $length;
        }
    }

    protected function _validateMinLength(core\collection\IInputTree $node, $value, $length=null) {
        if($length === null) {
            $length = mb_strlen($value);
        }

        if($this->_minLength > 0 && $length < $this->_minLength) {
            $node->addError('minLength', $this->_handler->_(
                array(
                    'n = 1 || n = -1' => 'This field must be at least %min% character',
                    '*' => 'This field must be at least %min% characters'
                ),
                array('%min%' => $this->_minLength),
                $this->_minLength
            ));
        }
    }
}


interface IMaxLengthField extends IField {
    public function setMaxLength($length);
    public function getMaxLength();
}

trait TMaxLengthField {

    protected $_maxLength = null;

    public function setMaxLength($length) {
        if($length !== null) {
            $length = (int)$length;

            if(empty($length)) {
                $length = 0;
            }

            if($length < 0) {
                $length = 0;
            }
        }

        $this->_maxLength = $length;
        return $this;
    }

    public function getMaxLength() {
        return $this->_maxLength;
    }

    protected function _setDefaultMaxLength($length) {
        if($this->_maxLength === null) {
            $this->_maxLength = $length;
        }
    }

    protected function _validateMaxLength(core\collection\IInputTree $node, $value, $length=null) {
        if($length === null) {
            $length = mb_strlen($value);
        }

        if($this->_maxLength !== null && $length > $this->_maxLength) {
            $node->addError('maxLength', $this->_handler->_(
                array(
                    'n = 1 || n = -1' => 'This field must not me more than %max% character',
                    '*' => 'This field must not me more than %max% characters'
                ),
                array('%max%' => $this->_maxLength),
                $this->_maxLength
            ));
        }
    }
}



interface ISanitizingField extends IField {
    public function setSanitizer(Callable $sanitizer);
    public function getSanitizer();
}

trait TSanitizingField {

    protected $_sanitizer;
    
    public function setSanitizer(Callable $sanitizer) {
        $this->_sanitizer = $sanitizer;
        return $this;
    }
    
    public function getSanitizer() {
        return $this->_sanitizer;
    }

    protected function _sanitizeValue($value) {
        if($this->_sanitizer) {
            $value = call_user_func_array($this->_sanitizer, [$value]);
        }

        return $value;
    }
}

interface IBooleanField extends IField {}
interface IDateField extends IField, IRangeField {}
interface IEmailField extends IField {}
interface IFloatField extends IField, IRangeField {}
interface IIdListField extends IField, ISanitizingField {}
interface IIntegerField extends IField, IRangeField {}
interface IPasswordField extends IField, IMinLengthField {}

interface ITextField extends IField, ISanitizingField, IMinLengthField, IMaxLengthField {
    public function setPattern($pattern);
    public function getPattern();
}

interface IUrlField extends IField {}
