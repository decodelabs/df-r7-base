<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate;

use df;
use df\core;
use df\opal;
use df\arch;
use df\mesh;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class BadMethodCallException extends \BadMethodCallException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}



// Interfaces
interface IHandler extends \ArrayAccess, core\lang\IChainable {
    public function addField($name, $type);
    public function addRequiredField($name, $type);
    public function newField($name, $type);
    public function newRequiredField($name, $type);
    public function getTargetField();
    public function endField();
    public function hasField($name);
    public function getField($name);
    public function getFields();
    public function removeField($name);
    public function getValues();
    public function getValue($name);
    public function setValue($name, $value);
    public function shouldSanitizeAll($flag=null);
    public function setRequireGroupFulfilled($name);
    public function setRequireGroupUnfulfilled($name, $field);
    public function checkRequireGroup($name);
    public function setDataMap(array $map=null);
    public function getDataMap();
    public function hasMappedField($name);
    
    public function isValid();
    public function validate($data);
    public function applyTo(&$targetRecord, array $fields=null);
}



interface IField {
    public function getHandler();
    public function getName();
    public function setRecordName($name);
    public function getRecordName();
    public function isRequired($flag=null);
    public function isOptional($flag=null);
    public function shouldSanitize($flag=null);
    public function setCustomValidator($validator=null);
    public function getCustomValidator();
    public function setRequireGroup($name);
    public function getRequireGroup();
    public function setToggleField($name);
    public function getToggleField();
    public function setMessageGenerator($generator=null);
    public function getMessageGenerator();
    
    public function validate(core\collection\IInputTree $node);
    public function applyValueTo(&$record, $value);
}



// Range
interface IRangeField extends IField {
    public function setRange($min, $max);
    public function setMin($min);
    public function getMin();
    public function setMax($max);
    public function getMax();
}

trait TRangeField {

    protected $_min;
    protected $_max;

    public function setRange($min, $max) {
        return $this->setMin($min)->setMax($max);
    }

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
            $this->_applyMessage($node, 'min', $this->_handler->_(
                'This field must be at least %min%',
                ['%min%' => $this->_min]
            ));
        }
        
        if($this->_max !== null && $value > $this->_max) {
            $this->_applyMessage($node, 'max', $this->_handler->_(
                'This field must not be more than %max%',
                ['%max%' => $this->_max]
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
            $this->_applyMessage($node, 'minLength', $this->_handler->_(
                [
                    'n = 1' => 'This field must contain at least %min% character',
                    '*' => 'This field must contain at least %min% characters'
                ],
                ['%min%' => $this->_minLength],
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
            $this->_applyMessage($node, 'maxLength', $this->_handler->_(
                [
                    'n = 1' => 'This field must not contain more than %max% character',
                    '*' => 'This field must not contain more than %max% characters'
                ],
                ['%max%' => $this->_maxLength],
                $this->_maxLength
            ));
        }
    }
}



// Options
interface IOptionProviderField extends IField {
    public function setOptions(array $options);
    public function getOptions();
}

trait TOptionProviderField {

    protected $_options = null;
    
    public function setOptions(array $options) {
        $this->_options = $options;
        return $this;
    }

    public function getOptions() {
        return $this->_options;
    }
}


// Sanitizer
interface ISanitizingField extends IField {
    public function setSanitizer($sanitizer);
    public function getSanitizer();
    public function setDefaultValue($value);
    public function getDefaultValue();
}

trait TSanitizingField {

    protected $_sanitizer;
    protected $_defaultValue;
    
    public function setSanitizer($sanitizer) {
        if($sanitizer !== null) {
            $sanitizer = core\lang\Callback::factory($sanitizer);
        }

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

    protected function _sanitizeValue($value, $runSanitizer=true) {
        if($value === '') {
            $value = null;
        }

        if($value === null) {
            $value = $this->_defaultValue;
        }

        if($this->_sanitizer && $runSanitizer) {
            $value = $this->_sanitizer->invoke($value, $this);
        }

        return $value;
    }
}



// Storage unit
interface IStorageAwareField extends IField {
    public function setStorageAdapter($adapter);
    public function getStorageAdapter();
}

trait TStorageAwareField {

    protected $_storageAdapter;

    public function setStorageAdapter($adapter) {
        if(!$adapter instanceof opal\query\IAdapter && $adapter !== null) {
            $adapter = mesh\Manager::getInstance()->fetchEntity($adapter);

            if(!$adapter instanceof opal\query\IAdapter) {
                throw new InvalidArgumentException('Invalid storage adapter for validator field '.$this->_name);
            }
        }

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
    public function addUniqueFilters(array $filters);
    public function addUniqueFilter($field, $value, $in=true);
    public function removeUniqueFilter($field);
    public function getUniqueFilters();
    public function clearUniqueFilters();
    public function setUniqueErrorMessage($message);
    public function getUniqueErrorMessage();
}

trait TUniqueCheckerField {

    protected $_storageField;
    protected $_uniqueFilters = [];
    protected $_uniqueErrorMessage;

    public function setStorageField($field) {
        $this->_storageField = $field;
        return $this;
    }

    public function getStorageField() {
        return $this->_storageField;
    }

    public function setUniqueFilterId($id) {
        $this->addUniqueFilter('@primary', $id, false);
        return $this;
    }

    public function getUniqueFilterId() {
        if(isset($this->_uniqueFilters['@primary'])) {
            return $this->_uniqueFilters['@primary'];
        }
    }

    public function addUniqueFilters(array $filters) {
        foreach($filters as $key => $value) {
            $this->addUniqueFilter($key, $value);
        }

        return $this;
    }

    public function addUniqueFilter($key, $value, $in=true) {
        $this->_uniqueFilters[$key] = [
            'value' => $value,
            'inclusive' => (bool)$in
        ];

        return $this;
    }

    public function removeUniqueFilter($field) {
        unset($this->_uniqueFilters[$field]);
        return $this;
    }

    public function getUniqueFilters() {
        return $this->_uniqueFilters;
    }

    public function clearUniqueFilters() {
        $this->_uniqueFilters = [];
        return $this;
    }


    public function setUniqueErrorMessage($message) {
        $this->_uniqueErrorMessage = $message;
        return $this;
    }

    public function getUniqueErrorMessage() {
        return $this->_uniqueErrorMessage;
    }

    protected function _validateUnique(core\collection\IInputTree $node, $value) {
        if(!$this->_storageAdapter) {
            return;
        }

        if(null === ($fieldName = $this->_storageField)) {
            if($this->_recordName) {
                $fieldName = $this->_recordName;
            } else {
                $fieldName = $this->_name;
            }
        }

        $query = (new opal\query\EntryPoint())
            ->select()
            ->from($this->_storageAdapter, 'checkUnit')
            ->where($fieldName, '=', $value);

        if(!empty($this->_uniqueFilters)) {
            foreach($this->_uniqueFilters as $field => $set) {
                $value = $set['value'];

                if(is_callable($value)) {
                    $value($query, $field);
                } else {
                    $query->where($field, $set['inclusive'] ? '=' : '!=', $value);
                }
            }
        }

        if($query->count()) {
            $message = $this->_uniqueErrorMessage;

            if($message === null) {
                $message = $this->_handler->_('That value has already been entered before');
            }

            $this->_applyMessage($node, 'unique', $message);
        }
    }
}



// Actual
interface IBooleanField extends IField, ISanitizingField {}

interface IColorField extends IField, ISanitizingField {}

interface ICurrencyField extends IField, IRangeField {
    public function setInputUnit($unit);
    public function getInputUnit();
}

interface IDateField extends IField, IRangeField, ISanitizingField {
    public function shouldDefaultToNow($flag=null);
    public function mustBePast($flag=null);
    public function mustBeFuture($flag=null);
    public function setExpectedFormat($format);
    public function getExpectedFormat();
}

interface IDelegateField extends IField {
    public function fromForm(arch\form\IForm $form, $name=null);
    public function setDelegate(arch\form\IDelegate $delegate);
    public function getDelegate();
}

interface IDurationField extends IField, IRangeField {
    public function setInputUnit($unit);
    public function getInputUnit();
}

interface IEmailField extends IField, IUniqueCheckerField {}

interface IEnumField extends IField, IOptionProviderField {}

interface IFloatField extends IField, IRangeField {}

interface IIdListField extends IField, ISanitizingField {
    public function shouldUseKeys($flag=null);
}

interface ITextListField extends IField, ISanitizingField {}
interface IIntegerField extends IField, IRangeField {}

interface IPasswordField extends IField, IMinLengthField {
    public function setMinStrength($strength);
    public function getMinStrength();
    public function shouldCheckStrength($flag=null);
}

interface ISetField extends IField {
    public function setOptions(array $options);
    public function getOptions();    
}

interface ISlugField extends IField, ISanitizingField, IUniqueCheckerField, IMinLengthField, IMaxLengthField {
    public function allowPathFormat($flag=null);
    public function allowAreaMarker($flag=null);
    public function setDefaultValueField($field);
    public function getDefaultValueField();
    public function shouldGenerateIfEmpty($flag=null);
}

interface IStructureField extends IField {
    
}

interface ITextField extends IField, ISanitizingField, IMinLengthField, IMaxLengthField {
    public function setPattern($pattern);
    public function getPattern();

    public function setMinWordLength($length);
    public function getMinWordLength();
    public function setMaxWordLength($length);
    public function getMaxWordLength();
}

interface IUrlField extends IField {}
interface IVideoEmbedField extends IField {}
