<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\spur\analytics\adapter;

use DecodeLabs\Exceptional;
use DecodeLabs\R7\Config\Analytics as AnalyticsConfig;
use DecodeLabs\R7\Legacy;

use df\core;
use df\spur;

abstract class Base implements spur\analytics\IAdapter
{
    protected $_options = [];
    protected $_defaultUserAttributes = [];

    public static function loadAllFromConfig($enabled = true)
    {
        $config = AnalyticsConfig::load();
        $output = [];

        foreach ($config->getAdapters() as $name => $info) {
            if (
                $enabled &&
                !($info->get('enabled') ?? true)
            ) {
                continue;
            }

            try {
                $adapter = self::factory(
                    $name,
                    $info->options->toArray(),
                    $info->userAttributes->toArray()
                );
            } catch (NotFoundException $e) {
                continue;
            }

            $output[$adapter->getName()] = $adapter;
        }

        return $output;
    }

    public static function loadFromConfig($name)
    {
        $config = AnalyticsConfig::load();

        if (null === ($info = $config->getAdapter($name))) {
            throw Exceptional::NotFound(
                'Adapter ' . $name . ' could not be found'
            );
        }

        $output = self::factory(
            $name,
            $info->options->toArray(),
            $info->userProperties->toArray()
        );

        return $output;
    }

    public static function loadAll()
    {
        $output = [];

        foreach (Legacy::getLoader()->lookupClassList('spur/analytics/adapter') as $name => $class) {
            try {
                $adapter = self::factory($name);
            } catch (NotFoundException $e) {
                continue;
            }

            $output[$adapter->getName()] = $adapter;
        }

        asort($output);
        return $output;
    }

    public static function factory($name, array $options = [], array $defaultUserAttributes = [])
    {
        $class = 'df\\spur\\analytics\\adapter\\' . ucfirst($name);

        if (!class_exists($class)) {
            throw Exceptional::NotFound(
                'Adapter ' . $name . ' could not be found'
            );
        }

        return new $class($options, $defaultUserAttributes);
    }

    public function __construct(array $options = [], array $defaultUserAttributes = [])
    {
        $this->setOptions($options);
        $this->setDefaultUserAttributes($defaultUserAttributes);
    }

    public function getName(): string
    {
        $parts = explode('\\', get_class($this));
        return (string)array_pop($parts);
    }


    // Options
    public function setOptions(array $options)
    {
        foreach ($options as $key => $val) {
            $this->setOption($key, $val);
        }

        return $this;
    }

    public function setOption($key, $val)
    {
        $this->_options[$key] = $val;
        return $this;
    }

    public function getOption($key, $default = null)
    {
        if (isset($this->_options[$key])) {
            return $this->_options[$key];
        }

        return $default;
    }

    public function getOptions()
    {
        return $this->_options;
    }

    public function getRequiredOptions()
    {
        return array_keys($this->_options);
    }

    public function clearOptions()
    {
        $this->_options = [];
        return $this;
    }

    public function validateOptions(core\collection\IInputTree $values, $update = false)
    {
        $this->_validateOptions($values);

        if ($update && $values->isValid()) {
            $this->setOptions($values->toArray());
        }

        return $this;
    }

    protected function _validateOptions(core\collection\IInputTree $values)
    {
    }


    public function setDefaultUserAttributes(array $attributes)
    {
        $available = spur\analytics\Handler::getAvailableUserAttributes();
        $this->_defaultUserAttributes = [];

        foreach ($attributes as $key => $value) {
            if (is_string($key)) {
                $attribute = $key;
                $map = $value;
            } else {
                $attribute = $value;
                $map = null;
            }

            if (in_array($attribute, $available)) {
                $this->_defaultUserAttributes[$attribute] = $map;
            }
        }

        return $this;
    }

    public function getDefaultUserAttributes()
    {
        return array_keys($this->_defaultUserAttributes);
    }

    public function getDefaultUserAttributeMap()
    {
        return $this->_defaultUserAttributes;
    }
}
