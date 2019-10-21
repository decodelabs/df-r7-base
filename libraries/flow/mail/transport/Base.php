<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mail\transport;

use df;
use df\core;
use df\flow;

abstract class Base implements flow\mail\ITransport
{
    public static function getAllDefaultConfigValues()
    {
        $output = [];

        foreach (df\Launchpad::$loader->lookupClassList('flow/mail/transport') as $name => $class) {
            $output[$name] = $class::getDefaultConfigValues();
        }

        return $output;
    }

    public static function getDefaultConfigValues()
    {
        return [];
    }

    public static function factory($name)
    {
        if (!$class = self::getTransportClass($name)) {
            throw new flow\mail\RuntimeException(
                'Mail transport '.$name.' could not be found'
            );
        }

        $config = flow\mail\Config::getInstance();
        $settings = $config->getTransportSettings($name);

        return new $class($settings);
    }

    public static function getTransportClass($name)
    {
        $class = 'df\\flow\\mail\\transport\\'.$name;

        if (class_exists($class)) {
            return $class;
        }

        return null;
    }

    public static function isValidTransport($name)
    {
        return (bool)self::getTransportClass($name);
    }

    public static function getAvailableTransports()
    {
        $output = [];

        foreach (df\Launchpad::$loader->lookupClassList('flow/mail/transport') as $name => $class) {
            $output[$name] = $class::getDescription();
        }

        return $output;
    }

    public static function getName(): string
    {
        $parts = explode('\\', get_called_class());
        return array_pop($parts);
    }
}
