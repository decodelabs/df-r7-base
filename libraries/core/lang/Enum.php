<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\lang;

use df;
use df\core;

abstract class Enum implements IEnum, core\IDumpable {

    use core\TStringProvider;
    use core\TStringValueProvider;

    protected static $_options;
    protected static $_labels;
    protected $_index;

    public static function factory($value) {
        if($value instanceof static) {
            return $value;
        }

        return new static($value);
    }

    protected function __construct($value) {
        static::getOptions();
        $this->_index = $this->_normalizeIndex($value);
    }

    protected function _normalizeIndex($value) {
        if(is_numeric($value) && isset(static::$_options[$value])) {
            $value = (int)$value;
        } else {
            if(in_array($value, static::$_options)) {
                $value = array_search($value, static::$_options);
            } else if(in_array($value, static::$_labels)) {
                $value = array_search($value, static::$_labels);
            } else {
                throw new InvalidArgumentException(
                    $value.' is not a valid enum option'
                );
            }
        }

        return $value;
    }

    public static function getOptions() {
        if(!static::$_options) {
            $reflection = new \ReflectionClass(get_called_class());
            static::$_options = static::$_labels = [];

            foreach($reflection->getConstants() as $name => $label) {
                static::$_options[] = lcfirst(str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $name)))));

                if(!strlen($label)) {
                    $label = ucwords(strtolower(str_replace('_', ' ', $name)));
                }

                static::$_labels[] = $label;
            }
        }

        return static::$_options;
    }

    public static function getLabels() {
        $output = [];

        foreach(static::getOptions() as $key => $option) {
            $output[$option] = static::$_labels[$key];
        }

        return $output;
    }

    public function getIndex() {
        return $this->_index;
    }

    public function getOption() {
        return static::$_options[$this->_index];
    }

    public function getLabel() {
        return static::$_labels[$this->_index];
    }

    public static function label($option) {
        return self::factory($option)->getLabel();
    }

    public function toString() {
        return static::$_labels[$this->_index];
    }

    public function getStringValue($default='') {
        return static::$_options[$this->_index];
    }

    public function is($value) {
        return $this->_index == self::factory($value)->_index;
    }

    public static function __callStatic($name, array $args) {
        if(defined('static::'.$name)) {
            return new static(constant('static::'.$name));
        }

        throw new LogicException(
            'Enum value '.$name.' has not been defined'
        );
    }


// Dump
    public function getDumpProperties() {
        return static::$_options[$this->_index].' ('.$this->_index.')';
    }
}