<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flow\mail\transport;

use DecodeLabs\Exceptional;
use DecodeLabs\R7\Config\Mail as MailConfig;
use DecodeLabs\R7\Legacy;
use df\flow;

abstract class Base implements flow\mail\ITransport
{
    public static function getAllDefaultConfigValues()
    {
        $output = [];

        foreach (Legacy::getLoader()->lookupClassList('flow/mail/transport') as $name => $class) {
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
            throw Exceptional::Runtime(
                'Mail transport ' . $name . ' could not be found'
            );
        }

        $config = MailConfig::load();
        $settings = $config->getTransportSettings($name);

        return new $class($settings);
    }

    public static function getTransportClass($name)
    {
        $class = 'df\\flow\\mail\\transport\\' . $name;

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

        foreach (Legacy::getLoader()->lookupClassList('flow/mail/transport') as $name => $class) {
            $output[$name] = $class::getDescription();
        }

        return $output;
    }

    public static function getName(): string
    {
        $parts = explode('\\', get_called_class());
        return (string)array_pop($parts);
    }
}
