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


interface IHandler extends \ArrayAccess, core\lang\IChainable {
    public function addField(string $name, string $type=null);
    public function addRequiredField(string $name, string $type=null);
    public function addAutoField(string $key);

    public function newField(string $name, string $type=null): IField;
    public function newRequiredField(string $name, string $type=null): IField;
    public function newAutoField(string $key): IField;

    public function getTargetField(): ?IField;
    public function endField();

    public function hasField(string $name): bool;
    public function getField(string $name): ?IField;
    public function getFields(): array;
    public function removeField(string $name);

    public function getValues();
    public function getValue(string $name);
    public function setValue(string $name, $value);
    public function isEmpty(): bool;

    public function setRequireGroupFulfilled(string $name);
    public function setRequireGroupUnfulfilled(string $name, string $field);
    public function checkRequireGroup(string $name);

    public function setDataMap(array $map=null);
    public function getDataMap(): ?array;
    public function hasMappedField(string $name);

    public function validate($data, array $fields=null);
    public function getCurrentData(): ?core\collection\IInputTree;
    public function isValid(): bool;
    public function applyTo(&$targetRecord, array $fields=null);
}



interface IField extends core\constraint\IRequirable, core\constraint\IOptional, core\collection\IErrorContainer {
    // Names
    public function getName(): string;
    public function setRecordName(?string $name);
    public function getRecordName(): string;


    // Require group
    public function setRequireGroup(?string $name);
    public function getRequireGroup(): ?string;
    public function setToggleField(?string $name);
    public function getToggleField(): ?string;

    // Sanitizing
    public function setSanitizer(?callable $sanitizer);
    public function getSanitizer(): ?callable;
    public function setDefaultValue($value);
    public function getDefaultValue();

    // Extension
    public function extend(?callable $extension);
    public function getExtension(): ?callable;

    // Custom validator
    public function setCustomValidator(?callable $validator);
    public function getCustomValidator(): ?callable;

    // Errors
    public function getDataNode(): ?core\collection\IInputTree;

    public function setMessageGenerator(?callable $generator);
    public function getMessageGenerator(): ?callable;

    // Apply
    public function validate();
    public function applyValueTo(&$record, $value);
    public function setApplicator(?callable $applicator);
    public function getApplicator(): ?callable;
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

    protected function _validateRange($value) {
        if($this->_min !== null && $value < $this->_min) {
            $this->addError('min', $this->validator->_(
                'This field must be at least %min%',
                ['%min%' => $this->_min]
            ));
        }

        if($this->_max !== null && $value > $this->_max) {
            $this->addError('max', $this->validator->_(
                'This field must not be more than %max%',
                ['%max%' => $this->_max]
            ));
        }
    }
}



// Min length
interface IMinLengthField extends IField {
    public function setMinLength(?int $length);
    public function getMinLength(): ?int;
}

trait TMinLengthField {

    protected $_minLength = null;

    public function setMinLength(?int $length) {
        if($length !== null) {
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

    public function getMinLength(): ?int {
        return $this->_minLength;
    }

    protected function _setDefaultMinLength(?int $length) {
        if($this->_minLength === null) {
            $this->_minLength = $length;
        }
    }

    protected function _validateMinLength(string $value, int $length=null) {
        if($length === null) {
            $length = mb_strlen($value);
        }

        if($this->_minLength > 0 && $length < $this->_minLength) {
            $this->addError('minLength', $this->validator->_(
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
    public function setMaxLength(?int $length);
    public function getMaxLength(): ?int;
}

trait TMaxLengthField {

    protected $_maxLength = null;

    public function setMaxLength(?int $length) {
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

    public function getMaxLength(): ?int {
        return $this->_maxLength;
    }

    protected function _setDefaultMaxLength(?int $length) {
        if($this->_maxLength === null) {
            $this->_maxLength = $length;
        }
    }

    protected function _validateMaxLength(string $value, int $length=null) {
        if($length === null) {
            $length = mb_strlen($value);
        }

        if($this->_maxLength !== null && $length > $this->_maxLength) {
            $this->addError('maxLength', $this->validator->_(
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
    public function getOptions(): ?array;
}

trait TOptionProviderField {

    protected $_options = null;
    protected $_type = null;

    public function setOptions(array $options) {
        $this->_options = $options;
        return $this;
    }

    public function getOptions(): ?array {
        return $this->_options;
    }

    public function setType($type) {
        if($type !== null) {
            if(is_string($type) && false === strpos($type, '://')) {
                $type = 'type://'.$type;
            }

            $type = mesh\Manager::getInstance()->fetchEntity($type);

            if($type instanceof core\lang\ITypeRef) {
                $type->checkType('core/lang/IEnum');
            } else if(!$type instanceof core\lang\IEnumFactory) {
                throw core\Error::EArgument(
                    'Type cannot provide an enum'
                );
            }
        }

        $this->_type = $type;
        return $this;
    }

    public function getType() {
        return $this->_type;
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
        if($adapter instanceof opal\record\IRecordAdapterProvider) {
            $adapter = $adapter->getAdapter();
        }

        if(!$adapter instanceof opal\query\IAdapter && $adapter !== null) {
            $adapter = mesh\Manager::getInstance()->fetchEntity($adapter);

            if(!$adapter instanceof opal\query\IAdapter) {
                throw core\Error::EArgument(
                    'Invalid storage adapter for validator field '.$this->_name
                );
            }
        }

        $this->_storageAdapter = $adapter;
        return $this;
    }

    public function getStorageAdapter() {
        return $this->_storageAdapter;
    }
}

interface IRecordManipulatorField extends IStorageAwareField, opal\query\IFilterConsumer {
    public function setRecord(opal\record\IRecord $record);
    public function setRecordId($id);
    public function getRecordId();
}

trait TRecordManipulatorField {

    protected $_recordId;

    public function setRecord(opal\record\IRecord $record) {
        $this->setStorageAdapter($record->getAdapter());

        if(!$record->isNew()) {
            $this->setRecordId($record->getPrimaryKeySet());
        }

        return $this;
    }

    public function setRecordId($id) {
        $this->_recordId = $id;
        return $this;
    }

    public function getRecordId() {
        return $this->_recordId;
    }
}



interface IUniqueCheckerField extends IRecordManipulatorField {
    public function setUniqueErrorMessage($message);
    public function getUniqueErrorMessage();
}


trait TUniqueCheckerField {

    protected $_uniqueErrorMessage;

    public function setUniqueErrorMessage($message) {
        $this->_uniqueErrorMessage = $message;
        return $this;
    }

    public function getUniqueErrorMessage() {
        return $this->_uniqueErrorMessage;
    }

    protected function _validateUnique(&$value, $rename=false) {
        if(!$this->_storageAdapter) {
            return;
        }

        $fieldName = $this->getRecordName();

        if($rename) {
            $exists = false;
            $counter = 0;
            $output = $value;

            while($this->_getUniqueCheckQuery($fieldName, $output)->count()) {
                $output = $value.'-'.(++$counter);
                $output = $this->_sanitizeValue($output);
            }

            $value = $output;
        } else {
            $exists = $this->_getUniqueCheckQuery($fieldName, $value)->count();
        }

        if($exists) {
            $message = $this->_uniqueErrorMessage;

            if($message === null) {
                $message = $this->validator->_('That value has already been entered before');
            }

            $this->addError('unique', $message);
        }
    }

    protected function _getUniqueCheckQuery($fieldName, $value) {
        $query = opal\query\Initiator::factory()
            ->beginSelect()
            ->from($this->_storageAdapter, 'checkUnit')
            ->where($fieldName, '=', $value);

        if($this->_recordId !== null) {
            $query->where('@primary', '!=', $this->_recordId);
        }

        $this->applyFilters($query);
        return $query;
    }
}



// Required value
interface IRequiredValueField extends IField {
    public function setRequiredValue($value);
    public function getRequiredValue();
}

trait TRequiredValueField {

    protected $_requiredValue = null;

    public function setRequiredValue($value) {
        if($value !== null) {
            $value = $this->_prepareRequiredValue($value);
        }

        $this->_requiredValue = $value;
        return $this;
    }

    public function getRequiredValue() {
        return $this->_requiredValue;
    }

    protected function _prepareRequiredValue($value) {
        return $value;
    }


    protected function _checkRequiredValue($value, $isRequired=null) {
        if($isRequired === null) {
            $isRequired = $this->_isRequiredAfterToggle($value);
        }

        if($this->_requiredValue === null
        || ($value === null && !$isRequired)) {
            return;
        }

        $test = $this->_prepareRequiredValue($value);

        if($test === null && !$isRequired) {
            return;
        }

        $isCorrect = $test === $this->_requiredValue;

        if(!$isCorrect && !$isRequired && $test === false) {
            return;
        }

        if(!$isCorrect) {
            $this->addError('incorrect',
                is_bool($this->_requiredValue) ?
                    $this->validator->_('You must complete this field') :
                    $this->validator->_('Please enter the correct value')
            );
        }
    }
}



// Actual
interface IBooleanField extends IField, IRequiredValueField {}

interface IColorField extends IField {}

interface ICurrencyField extends IField, IRangeField {
    public function setCurrency($unit);
    public function getCurrency();
    public function allowSelection(bool $flag=null);
    public function setCurrencyFieldName($name);
    public function getCurrencyFieldName();
}

interface IDateField extends IField, IRangeField {
    public function shouldDefaultToNow(bool $flag=null);
    public function mustBePast(bool $flag=null);
    public function mustBeFuture(bool $flag=null);
    public function setExpectedFormat($format);
    public function getExpectedFormat();
}

interface IDelegateField extends IField {
    public function fromForm(arch\node\IForm $form, string $name=null);
    public function setDelegate(arch\node\IDelegate $delegate);
    public function getDelegate(): ?arch\node\IDelegate;
}

interface IDurationField extends IField, IRangeField {
    public function setInputUnit($unit);
    public function getInputUnit();
}

interface IEmailField extends IField, IUniqueCheckerField {}

interface IEnumField extends IField, IOptionProviderField {}

interface IEntityLocatorField extends IField {}

interface IFloatingPointField extends IField, IRangeField {}

interface IGuidField extends IField {}

interface IIdListField extends IField {
    public function shouldUseKeys(bool $flag=null);
}

interface ITextListField extends IField {}
interface ITimeField extends IField {}
interface IIntegerField extends IField, IRangeField {}

interface IPasswordField extends IField, IMinLengthField {
    public function setMinStrength($strength);
    public function getMinStrength();
    public function shouldCheckStrength(bool $flag=null);
}

interface IRecaptchaField extends IField {}

interface ISetField extends IField, IOptionProviderField {}

interface ISlugField extends IField, IUniqueCheckerField, IMinLengthField, IMaxLengthField {
    public function allowPathFormat(bool $flag=null);
    public function allowAreaMarker(bool $flag=null);
    public function allowRoot(bool $flag=null);
    public function setDefaultValueField($field, $sanitizer=null);
    public function getDefaultValueField();
    public function shouldGenerateIfEmpty(bool $flag=null);
    public function shouldRenameOnConflict(bool $flag=null);
}

interface IStructureField extends IField {
    public function shouldAllowEmpty(bool $flag=null);
}

interface ITextField extends IField, IUniqueCheckerField, IMinLengthField, IMaxLengthField {
    public function setPattern($pattern);
    public function getPattern();

    public function setMinWords($length);
    public function getMinWords();
    public function setMaxWords($length);
    public function getMaxWords();

    public function shouldTrim(bool $flag=null);
}

interface IUrlField extends IField {
    public function allowInternal(bool $flag=null);
}

interface IVideoEmbedField extends IField {}

interface IWeightField extends IField, IStorageAwareField, IRecordManipulatorField {

}
