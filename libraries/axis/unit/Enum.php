<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit;

use df;
use df\core;
use df\axis;

abstract class Enum implements axis\IUnit, core\lang\IEnumFactory {

    use axis\TUnit;

    private $_options = null;
    private $_labels = null;

    public function getUnitType() {
        return 'enum';
    }

    public function factory($value) {
        return new Enum_Inst($this, $value);
    }

    public function normalize($value) {
        if(!strlen($value)) {
            return null;
        }

        return $this->factory($value)->getOption();
    }

    public function getOptions() {
        if(!$this->_options) {
            $reflection = new \ReflectionClass(get_called_class());
            $this->_options = $this->_labels = [];

            foreach($reflection->getConstants() as $name => $label) {
                if($name == 'DEFAULT_ACCESS') {
                    continue;
                }

                $this->_options[] = lcfirst(str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $name)))));

                if(!strlen($label)) {
                    $label = ucwords(strtolower(str_replace('_', ' ', $name)));
                }

                $this->_labels[] = $label;
            }
        }

        return $this->_options;
    }

    public function isOption($option) {
        $option = core\lang\Enum::normalizeOption($option);
        return in_array($option, $this->getOptions());
    }

    public function getLabels() {
        $output = [];

        foreach($this->getOptions() as $key => $option) {
            $output[$option] = $this->_labels[$key];
        }

        return $output;
    }

    public function getLabelList() {
        return $this->_labels;
    }

    public function label($option) {
        if(!strlen($option)) {
            return null;
        }

        return $this->factory($option)->getLabel();
    }


    public function getLt($option): array {
        $option = core\lang\Enum::normalizeOption($option);
        $options = $this->getOptions();

        if(false === ($key = array_search($option, $options, true))) {
            throw core\Error::EArgument('Invalid option: '.$option);
        }

        return array_slice($options, 0, $key);
    }

    public function getLte($option): array {
        $option = core\lang\Enum::normalizeOption($option);
        $options = $this->getOptions();

        if(false === ($key = array_search($option, $options, true))) {
            throw core\Error::EArgument('Invalid option: '.$option);
        }

        return array_slice($options, 0, $key + 1);
    }

    public function getGt($option): array {
        $option = core\lang\Enum::normalizeOption($option);
        $options = $this->getOptions();

        if(false === ($key = array_search($option, $options, true))) {
            throw core\Error::EArgument('Invalid option: '.$option);
        }

        return array_slice($options, $key + 1);
    }

    public function getGte($option): array {
        $option = core\lang\Enum::normalizeOption($option);
        $options = $this->getOptions();

        if(false === ($key = array_search($option, $options, true))) {
            throw core\Error::EArgument('Invalid option: '.$option);
        }

        return array_slice($options, $key);
    }
}

class Enum_Inst implements core\lang\IEnum {

    use core\TStringProvider;

    protected $_options;
    protected $_labels;
    protected $_index;

    public static function factory($value) {
        throw core\Error::EImplementation(
            'Unit enum factory is not accessible'
        );
    }

    public static function normalize($value) {
        throw core\Error::EImplementation(
            'Unit enum normalize is not accessible'
        );
    }

    public function __construct(Enum $unit, $value) {
        $this->_options = $unit->getOptions();
        $this->_labels = $unit->getLabelList();
        $this->_index = $this->_normalizeIndex($value);
    }

    protected function _normalizeIndex($value) {
        if($value instanceof core\lang\IEnum) {
            return $value->getIndex();
        }

        if(is_numeric($value) && isset($this->_options[$value])) {
            $value = (int)$value;
        } else {
            if(in_array($value, $this->_options)) {
                $value = array_search($value, $this->_options);
            } else if(in_array($value, $this->_labels)) {
                $value = array_search($value, $this->_labels);
            } else {
                throw core\Error::{'core/lang/EEnum,core/lang/EArgument'}(
                    $value.' is not a valid enum option'
                );
            }
        }

        return $value;
    }

    public static function getOptions() {
        if(isset($this)) {
            return $this->_options;
        }

        throw new core\lang\RuntimeException(
            'Unit enum static calls are not accessible'
        );
    }

    public static function isOption($option) {
        if(isset($this)) {
            $option = core\lang\Enum::normalizeOption($option);
            return in_array($option, $this->_options);
        }

        throw new core\lang\RuntimeException(
            'Unit enum static calls are not accessible'
        );
    }

    public static function getLabels() {
        if(isset($this)) {
            return $this->_labels;
        }

        throw new core\lang\RuntimeException(
            'Unit enum static calls are not accessible'
        );
    }

    public static function getLt($option): array {
        core\stub();
    }

    public static function getLte($option): array {
        core\stub();
    }

    public static function getGt($option): array {
        core\stub();
    }

    public static function getGte($option): array {
        core\stub();
    }

    public function getIndex() {
        return $this->_index;
    }

    public function getOption() {
        return $this->_options[$this->_index];
    }

    public function getLabel() {
        return $this->_labels[$this->_index];
    }

    public function toString(): string {
        return (string)$this->_labels[$this->_index];
    }

    public function getStringValue($default=''): string {
        return $this->_options[$this->_index];
    }


    public static function label($option) {
        if(isset($this)) {
            return $this->_unit->label($option);
        }

        throw new core\lang\RuntimeException(
            'Unit enum static calls are not accessible'
        );
    }

    public function is($value) {
        return $this->_index == $this->_normalizeIndex($value);
    }
}