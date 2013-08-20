<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate;

use df;
use df\core;
use df\opal;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class BadMethodCallException extends \BadMethodCallException implements IException {}



// Interfaces
interface IHandler extends \ArrayAccess {
    public function addField($name, $type);
    public function getField($name);
    public function getFields();
    public function getValues();
    public function getValue($name);
    public function shouldSanitize($flag=null);
    
    public function isValid();
    public function validate($data);
    public function applyTo(&$targetRecord, array $fields=null);
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



// Range
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



// Min length
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



// Max length
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



// Sanitizer
interface ISanitizingField extends IField {
    public function setSanitizer(Callable $sanitizer);
    public function getSanitizer();
    public function setDefaultValue($value);
    public function getDefaultValue();
}

trait TSanitizingField {

    protected $_sanitizer;
    protected $_defaultValue;
    
    public function setSanitizer(Callable $sanitizer) {
        $this->_sanitizer = $sanitizer;
        return $this;
    }
    
    public function getSanitizer() {
        return $this->_sanitizer;
    }

    public function setDefaultValue($value) {
        $this->_defaultValue = $value;
        return $this;
    }

    public function getDefaultValue() {
        return $this->_defaultValue;
    }

    protected function _sanitizeValue($value) {
        if($value == '') {
            $value = null;
        }

        if($value === null) {
            $value = $this->_defaultValue;
        }

        if($this->_sanitizer) {
            $value = call_user_func_array($this->_sanitizer, [$value, $this]);
        }

        return $value;
    }
}



// Storage unit
interface IStorageAwareField extends IField {
    public function setStorageAdapter(opal\query\IAdapter $adapter);
    public function getStorageAdapter();
}

trait TStorageAwareField {

    protected $_storageAdapter;

    public function setStorageAdapter(opal\query\IAdapter $adapter) {
        $this->_storageAdapter = $adapter;
        return $this;
    }

    public function getStorageAdapter() {
        return $this->_storageAdapter;
    }
}


interface IUniqueCheckerField extends IStorageAwareField {
    public function setStorageField($field);
    public function getStorageField();
    public function setUniqueFilterId($id);
    public function getUniqueFilterId();
    public function setUniqueErrorMessage($message);
    public function getUniqueErrorMessage();
}

trait TUniqueCheckerField {

    protected $_storageField;
    protected $_filterId;
    protected $_uniqueErrorMessage;

    public function setStorageField($field) {
        $this->_storageField = $field;
        return $this;
    }

    public function getStorageField() {
        return $this->_storageField;
    }

    public function setUniqueFilterId($id) {
        $this->_filterId = $id;
        return $this;
    }

    public function getUniqueFilterId() {
        return $this->_filterId;
    }

    public function setUniqueErrorMessage($message) {
        $this->_uniqueErrorMessage = $message;
        return $this;
    }

    public function getUniqueErrorMessage() {
        return $this->_uniqueErrorMessage;
    }

    protected function _validateUnique(core\collection\IInputTree $node, $value) {
        if($this->_storageAdapter) {
            if(null === ($fieldName = $this->_storageField)) {
                $fieldName = $this->_name;
            }

            $query = (new opal\query\EntryPoint())
                ->select()
                ->from($this->_storageAdapter, 'checkUnit')
                ->where($fieldName, '=', $value);

            if($this->_filterId !== null) {
                $query->where('@primary', '!=', $this->_filterId);
            }

            if($query->count()) {
                $message = $this->_uniqueErrorMessage;

                if($message === null) {
                    $message = $this->_handler->_('That value has already been entered before');
                }

                $node->addError('unique', $message);
            }
        }
    }
}



// Actual
interface IBooleanField extends IField, ISanitizingField {}

interface IDateField extends IField, IRangeField, ISanitizingField {
    public function shouldDefaultToNow($flag=null);
    public function mustBePast($flag=null);
    public function mustBeFuture($flag=null);
}

interface IDurationField extends IField, IRangeField {
    public function setInputUnit($unit);
    public function getInputUnit();
}

interface IEmailField extends IField, IUniqueCheckerField {}

interface IEnumField extends IField {
    public function setOptions(array $options);
    public function getOptions();    
}

interface IFloatField extends IField, IRangeField {}
interface IIdListField extends IField, ISanitizingField {}
interface IIntegerField extends IField, IRangeField {}
interface IPasswordField extends IField, IMinLengthField {}

interface ISlugField extends IField, ISanitizingField, IUniqueCheckerField {
    public function allowPathFormat($flag=null);
    public function setDefaultValueField($field);
    public function getDefaultValueField();
    public function shouldGenerateIfEmpty($flag=null);
}

interface ITextField extends IField, ISanitizingField, IMinLengthField, IMaxLengthField {
    public function setPattern($pattern);
    public function getPattern();
}

interface IUrlField extends IField {}
