<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\enum;

use df;
use df\core;
use df\axis;

abstract class Base implements axis\IUnit, core\lang\IEnumFactory {

    use axis\TUnit;

    private $_options = null;
    private $_labels = null;

    public function getUnitType() {
        return 'enum';
    }

    public function factory($value) {
        return new Base_Enum($this, $value);
    }

    public function normalize($value) {
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
}

class Base_Enum implements core\lang\IEnum {

    use core\TStringProvider;

    protected $_options;
    protected $_labels;
    protected $_index;

    public static function factory($value) {
        throw new core\lang\RuntimeException(
            'Unit enum factory is not accessible'
        );
    }

    public static function normalize($value) {
        throw new core\lang\RuntimeException(
            'Unit enum normalize is not accessible'
        );
    }

    public function __construct(Base $unit, $value) {
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
                throw new core\lang\InvalidArgumentException(
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

    public function getIndex() {
        return $this->_index;
    }

    public function getOption() {
        return $this->_options[$this->_index];
    }

    public function getLabel() {
        return $this->_labels[$this->_index];
    }

    public function toString() {
        return $this->_labels[$this->_index];
    }

    public function getStringValue($default='') {
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