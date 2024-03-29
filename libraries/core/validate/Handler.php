<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\validate;

use DecodeLabs\Exceptional;

use df\core;

class Handler implements IHandler
{
    use core\TTranslator;
    use core\lang\TChainable;

    protected $_values = [];
    protected $_fields = [];
    protected $_targetField = null;
    protected $_requireGroups = [];
    protected $_isValid = null;
    protected $_dataMap = [];

    public $data = null;



    // Fields
    public function addField(string $name, string $type = null)
    {
        $this->newField($name, $type);
        return $this;
    }

    public function addRequiredField(string $name, string $type = null)
    {
        $this->newField($name, $type)->isRequired(true);
        return $this;
    }

    public function addAutoField(string $key)
    {
        $this->newAutoField($key);
        return $this;
    }



    public function newField(string $name, string $type = null): IField
    {
        $this->endField();
        $field = core\validate\field\Base::factory($this, $type, $name);

        $this->_fields[$field->getName()] = $field;
        $this->_targetField = $field;

        return $field;
    }

    public function newRequiredField(string $name, string $type = null): IField
    {
        return $this->newField($name, $type)->isRequired(true);
    }

    public function newAutoField(string $key): IField
    {
        $isRequired = $isBoolean = false;

        if (substr($key, 0, 1) == '*') {
            $key = substr($key, 1);
            $isRequired = true;
        } elseif (substr($key, 0, 1) == '?') {
            $key = substr($key, 1);
            $isBoolean = true;
        }

        if ($isBoolean) {
            $field = $this->newField($key, 'boolean');
        } else {
            $field = $this->newField($key, 'text');
        }

        $field->isRequired($isRequired);
        return $field;
    }



    public function getTargetField(): ?IField
    {
        return $this->_targetField;
    }

    public function endField()
    {
        $this->_targetField = null;
        return $this;
    }

    public function __call($method, array $args)
    {
        if (!$this->_targetField) {
            throw Exceptional::Runtime(
                'There is no active target field to apply method ' . $method . ' to'
            );
        }

        if (!method_exists($this->_targetField, $method)) {
            throw Exceptional::BadMethodCall(
                'Target field ' . $this->_targetField->getName() . ' does not have method ' . $method
            );
        }

        $output = $this->_targetField->{$method}(...$args);

        if ($output === $this->_targetField) {
            return $this;
        }

        return $output;
    }



    public function hasField(string $name): bool
    {
        return isset($this->_fields[$name]);
    }

    public function getField(string $name): ?IField
    {
        if (!isset($this->_fields[$name])) {
            return null;
        }

        return $this->_fields[$name];
    }

    public function getFields(): array
    {
        return $this->_fields;
    }

    public function removeField(string $name)
    {
        unset($this->_fields[$name]);
        return $this;
    }



    // Values
    public function getValues()
    {
        if ($this->_isValid === null) {
            throw Exceptional::Setup(
                'This validator has not been run yet'
            );
        }

        return $this->_values;
    }

    public function getValue(string $name)
    {
        if ($this->_isValid === null) {
            throw Exceptional::Setup(
                'This validator has not been run yet'
            );
        }

        if (isset($this->_values[$name])) {
            return $this->_values[$name];
        }
    }

    public function setValue(string $name, $value)
    {
        if ($this->_isValid === null) {
            throw Exceptional::Setup(
                'This validator has not been run yet'
            );
        }

        $this->_values[$name] = $value;
        return $this;
    }

    public function isEmpty(): bool
    {
        foreach ($this->_values as $value) {
            if ($value !== null) {
                return false;
            }
        }

        return true;
    }

    public function offsetSet(
        mixed $offset,
        mixed $value
    ): void {
        $this->setValue($offset, $value);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->getValue($offset);
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->_values);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw Exceptional::BadMethodCall(
            'Validator values cannot be set via array access'
        );
    }





    // Require group
    public function setRequireGroupFulfilled(string $name)
    {
        $this->_requireGroups[$name] = true;
        return $this;
    }

    public function setRequireGroupUnfulfilled(string $name, string $field)
    {
        if (isset($this->_requireGroups[$name]) && $this->_requireGroups[$name] === true) {
            return $this;
        }

        $this->_requireGroups[$name][] = $field;
        return $this;
    }

    public function checkRequireGroup(string $name)
    {
        if (isset($this->_requireGroups[$name])) {
            return $this->_requireGroups[$name] === true;
        }

        return false;
    }


    // Map
    public function setDataMap(array $map = null)
    {
        if ($map === null) {
            $this->_dataMap = null;
        } else {
            $this->_dataMap = [];

            foreach ($map as $key => $value) {
                if (is_int($key)) {
                    $key = $value;
                }

                $this->_dataMap[$key] = $value;
            }
        }

        return $this;
    }

    public function getDataMap(): ?array
    {
        return $this->_dataMap;
    }

    protected function _getActiveDataMap()
    {
        $map = $this->_dataMap;

        foreach ($this->_fields as $name => $field) {
            if (!isset($map[$name])) {
                $map[$name] = $name;
            }
        }

        return $map;
    }

    public function hasMappedField(string $name)
    {
        if ($this->_dataMap) {
            return in_array($name, $this->_dataMap);
        } else {
            return isset($this->_fields[$name]);
        }
    }

    public function getMappedName($name)
    {
        if ($this->_dataMap) {
            $map = array_flip($this->_dataMap);

            if (isset($map[$name])) {
                return $map[$name];
            }
        }

        return $name;
    }






    // Validate
    public function validate($data, array $allowFields = null)
    {
        $this->endField();

        if (!$data instanceof core\collection\IInputTree) {
            $data = core\collection\InputTree::factory($data);
        }

        $this->_reset();
        $this->data = $data;

        $map = $this->_getActiveDataMap();

        foreach ($map as $fieldName => $dataName) {
            if ($allowFields !== null && !in_array($fieldName, $allowFields)) {
                continue;
            }

            if (!isset($this->_fields[$fieldName])) {
                continue;
            }

            $field = $this->_fields[$fieldName];

            if ($field->isOptional() && !isset($data->{$dataName})) {
                continue;
            }

            $node = $data->{$dataName};
            $field->data = $node;

            $this->_values[$fieldName] = $field->validate($node);

            if (!$node->isValid()) {
                $this->_isValid = false;
            }
        }

        foreach ($this->_requireGroups as $name => $fields) {
            if ($fields === true) {
                continue;
            }

            foreach ($fields as $field) {
                if (isset($map[$field])) {
                    $dataName = $map[$field];
                } else {
                    $dataName = $field;
                }

                $data->{$dataName}->addError('required', $this->_('You must enter a value in one of these fields'));
                $this->_isValid = false;
            }
        }

        return $this;
    }

    protected function _reset()
    {
        $this->_isValid = true;
        $this->_values = [];
        $this->_requireGroups = [];
    }

    public function isValid(): bool
    {
        if ($this->_isValid === null) {
            return true;
        }

        if (!$this->_isValid) {
            return false;
        }

        return $this->data->isValid();
    }

    public function getCurrentData(): ?core\collection\IInputTree
    {
        return $this->data;
    }

    public function applyTo(&$record, array $fields = null)
    {
        if (!is_array($record) && !$record instanceof \ArrayAccess) {
            throw Exceptional::InvalidArgument(
                'Target record does not implement ArrayAccess'
            );
        }

        if (empty($fields)) {
            $fields = array_keys($this->_values);
        }

        $map = $this->_getActiveDataMap();

        foreach ($fields as $key) {
            if (!isset($map[$key])) {
                continue;
            }

            if (!$this->data->{$key}->isValid()) {
                continue;
            }

            if (array_key_exists($key, $this->_values)) {
                $this->_fields[$key]->applyValueTo($record, $this->_values[$key]);
            }
        }

        return $this;
    }

    public function translate(array $args): string
    {
        return core\i18n\Manager::getInstance()->translate($args);
    }
}
