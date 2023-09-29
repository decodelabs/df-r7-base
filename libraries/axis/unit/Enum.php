<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis\unit;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;

use df\axis;
use df\core;

abstract class Enum implements axis\IUnit, core\lang\IEnumFactory
{
    use axis\TUnit;

    private $_options = null;
    private $_labels = null;

    public function getUnitType()
    {
        return 'enum';
    }

    public function factory($value)
    {
        return new Enum_Inst($this, $value);
    }

    public function normalize($value): ?string
    {
        if (!strlen((string)$value)) {
            return null;
        }

        return $this->factory($value)->getOption();
    }

    public function getOptions(): array
    {
        if (!$this->_options) {
            $reflection = new \ReflectionClass(get_called_class());
            $this->_options = $this->_labels = [];

            foreach ($reflection->getConstants() as $name => $label) {
                if ($name == 'DEFAULT_ACCESS') {
                    continue;
                }

                $this->_options[] = lcfirst(str_replace(' ', '', ucwords(strtolower(str_replace('_', ' ', $name)))));

                if (!strlen($label)) {
                    $label = ucwords(strtolower(str_replace('_', ' ', $name)));
                }

                $this->_labels[] = $label;
            }
        }

        return $this->_options;
    }

    public function isOption($option): bool
    {
        $option = core\lang\Enum::normalizeOption($option);
        return in_array($option, $this->getOptions());
    }

    public function getLabels(): array
    {
        $output = [];

        foreach ($this->getOptions() as $key => $option) {
            $output[$option] = $this->_labels[$key];
        }

        return $output;
    }

    public function getLabelList(): array
    {
        return $this->_labels;
    }

    public function label($option): ?string
    {
        if (!strlen((string)$option)) {
            return null;
        }

        return $this->factory($option)->getLabel();
    }


    public function getLt($option): array
    {
        $option = core\lang\Enum::normalizeOption($option);
        $options = $this->getOptions();

        if (false === ($key = array_search($option, $options, true))) {
            throw Exceptional::InvalidArgument(
                'Invalid option: ' . $option
            );
        }

        return array_slice($options, 0, (int)$key);
    }

    public function getLte($option): array
    {
        $option = core\lang\Enum::normalizeOption($option);
        $options = $this->getOptions();

        if (false === ($key = array_search($option, $options, true))) {
            throw Exceptional::InvalidArgument(
                'Invalid option: ' . $option
            );
        }

        return array_slice($options, 0, (int)$key + 1);
    }

    public function getGt($option): array
    {
        $option = core\lang\Enum::normalizeOption($option);
        $options = $this->getOptions();

        if (false === ($key = array_search($option, $options, true))) {
            throw Exceptional::InvalidArgument(
                'Invalid option: ' . $option
            );
        }

        return array_slice($options, (int)$key + 1);
    }

    public function getGte($option): array
    {
        $option = core\lang\Enum::normalizeOption($option);
        $options = $this->getOptions();

        if (false === ($key = array_search($option, $options, true))) {
            throw Exceptional::InvalidArgument(
                'Invalid option: ' . $option
            );
        }

        return array_slice($options, (int)$key);
    }
}

class Enum_Inst implements core\lang\IInstanceEnum
{
    use core\TStringProvider;

    protected $_options;
    protected $_labels;
    protected $_index;

    public static function factory($value)
    {
        throw Exceptional::Implementation(
            'Unit enum factory is not accessible'
        );
    }

    public static function normalize($value): ?string
    {
        throw Exceptional::Implementation(
            'Unit enum normalize is not accessible'
        );
    }

    public function __construct(Enum $unit, $value)
    {
        $this->_options = $unit->getOptions();
        $this->_labels = $unit->getLabelList();
        $this->_index = $this->_normalizeIndex($value);
    }

    protected function _normalizeIndex($value)
    {
        if ($value instanceof core\lang\IEnum) {
            return $value->getIndex();
        }

        if (is_numeric($value) && isset($this->_options[$value])) {
            $value = (int)$value;
        } else {
            if (in_array($value, $this->_options)) {
                $value = array_search($value, $this->_options);
            } elseif (in_array($value, $this->_labels)) {
                $value = array_search($value, $this->_labels);
            } else {
                throw Exceptional::{'df/core/lang/Enum,df/core/lang/InvalidArgument'}(
                    $value . ' is not a valid enum option'
                );
            }
        }

        return $value;
    }

    public function getOptions(): array
    {
        return $this->_options;
    }

    public function isOption($option): bool
    {
        $option = core\lang\Enum::normalizeOption($option);
        return in_array($option, $this->_options);
    }

    public function getLabels(): array
    {
        return $this->_labels;
    }

    public function getLt($option): array
    {
        Glitch::incomplete();
    }

    public function getLte($option): array
    {
        Glitch::incomplete();
    }

    public function getGt($option): array
    {
        Glitch::incomplete();
    }

    public function getGte($option): array
    {
        Glitch::incomplete();
    }

    public function getIndex(): int
    {
        return $this->_index;
    }

    public function getOption(): string
    {
        return $this->_options[$this->_index];
    }

    public function getLabel(): string
    {
        return $this->_labels[$this->_index];
    }

    public function toString(): string
    {
        return $this->_labels[$this->_index];
    }

    public function getStringValue($default = ''): string
    {
        return $this->_options[$this->_index];
    }


    public function label($option): string
    {
        Glitch::incomplete();
    }

    public function is($value): bool
    {
        return $this->_index == $this->_normalizeIndex($value);
    }
}
