<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\lang;

use df;
use df\core;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Dumpable;

abstract class Enum implements IStaticEnum, Dumpable
{
    use core\TStringProvider;
    use core\TStringValueProvider;

    protected static $_options;
    protected static $_labels;
    protected $_index;

    public static function factory($value)
    {
        if ($value instanceof static) {
            return $value;
        }

        return new static($value);
    }

    public static function normalize($value)
    {
        if (!strlen($value)) {
            return null;
        }

        return self::factory($value)->getOption();
    }

    protected function __construct($value)
    {
        static::getOptions();
        $this->_index = $this->_normalizeIndex($value);
    }

    protected function _normalizeIndex($value)
    {
        $class = get_class($this);

        if (is_numeric($value) && isset(self::$_options[$class][$value])) {
            $value = (int)$value;
        } else {
            if (in_array($value, self::$_options[$class])) {
                $value = array_search($value, self::$_options[$class]);
            } elseif (in_array($value, self::$_labels[$class])) {
                $value = array_search($value, self::$_labels[$class]);
            } else {
                throw Glitch::{'EEnum,EInvalidArgument'}(
                    $value.' is not a valid enum option'
                );
            }
        }

        return $value;
    }

    public static function getOptions()
    {
        $class = get_called_class();

        if (!isset(self::$_options[$class])) {
            $reflection = new \ReflectionClass(get_called_class());
            self::$_options[$class] = self::$_labels[$class] = [];

            foreach ($reflection->getConstants() as $name => $label) {
                self::$_options[$class][] = self::normalizeOption($name);

                if (!strlen($label)) {
                    $label = ucwords(strtolower(str_replace('_', ' ', $name)));
                }

                self::$_labels[$class][] = $label;
            }
        }

        return self::$_options[$class];
    }

    public static function isOption($option)
    {
        $option = self::normalizeOption($option);
        $options = self::getOptions();
        return in_array($option, $options);
    }

    public static function normalizeOption(?string $option)
    {
        $option = (string)preg_replace('/([a-z])([A-Z])/u', '$1 $2', (string)$option);
        $option = str_replace(['_', '-'], ' ', $option);
        return lcfirst(str_replace(' ', '', ucwords(strtolower($option))));
    }

    public static function getLabels()
    {
        $class = get_called_class();
        $output = [];

        foreach (static::getOptions() as $key => $option) {
            $output[$option] = self::$_labels[$class][$key];
        }

        return $output;
    }


    public static function getLt($option): array
    {
        $option = self::normalizeOption($option);
        $options = self::getOptions();

        if (false === ($key = array_search($option, $options, true))) {
            throw Glitch::EInvalidArgument('Invalid option: '.$option);
        }

        return array_slice($options, 0, (int)$key);
    }

    public static function getLte($option): array
    {
        $option = self::normalizeOption($option);
        $options = self::getOptions();

        if (false === ($key = array_search($option, $options, true))) {
            throw Glitch::EInvalidArgument('Invalid option: '.$option);
        }

        return array_slice($options, 0, (int)$key + 1);
    }

    public static function getGt($option): array
    {
        $option = self::normalizeOption($option);
        $options = self::getOptions();

        if (false === ($key = array_search($option, $options, true))) {
            throw Glitch::EInvalidArgument('Invalid option: '.$option);
        }

        return array_slice($options, (int)$key + 1);
    }

    public static function getGte($option): array
    {
        $option = self::normalizeOption($option);
        $options = self::getOptions();

        if (false === ($key = array_search($option, $options, true))) {
            throw Glitch::EInvalidArgument('Invalid option: '.$option);
        }

        return array_slice($options, (int)$key);
    }





    public function getIndex()
    {
        return $this->_index;
    }

    public function getOption()
    {
        return self::$_options[get_class($this)][$this->_index];
    }

    public function getLabel()
    {
        return self::$_labels[get_class($this)][$this->_index];
    }

    public static function label($option)
    {
        if (!strlen($option)) {
            return null;
        }

        return self::factory($option)->getLabel();
    }

    public function toString(): string
    {
        return (string)self::$_labels[get_class($this)][$this->_index];
    }

    public function getStringValue($default=''): string
    {
        return self::$_options[get_class($this)][$this->_index];
    }

    public function is($value)
    {
        return $this->_index == self::factory($value)->_index;
    }

    public static function __callStatic($name, array $args)
    {
        if (defined('static::'.$name)) {
            return new static(constant('static::'.$name));
        }

        throw Glitch::ELogic(
            'Enum value '.$name.' has not been defined'
        );
    }

    /**
     * Inspect for Glitch
     */
    public function glitchDump(): iterable
    {
        yield 'definition' => self::$_options[get_class($this)][$this->_index].' ('.$this->_index.')';
    }
}
