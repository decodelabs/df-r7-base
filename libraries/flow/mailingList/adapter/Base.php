<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mailingList\adapter;

use df;
use df\core;
use df\flow;

use DecodeLabs\Exceptional;

abstract class Base implements flow\mailingList\IAdapter
{
    const SETTINGS_FIELDS = [];

    public static function factory(core\collection\ITree $options): flow\mailingList\IAdapter
    {
        if (!$name = $options['adapter']) {
            throw Exceptional::{'df/flow/mailingList/Setup'}(
                'Mailing list adapter name has not been set in config'
            );
        }

        $class = 'df\\flow\\mailingList\\adapter\\'.ucfirst($name);

        if (!class_exists($class)) {
            throw Exceptional::{'df/flow/mailingList/Setup,NotFound'}(
                'Mailing list adapter '.$name.' could not be found'
            );
        }

        return new $class($options);
    }

    public static function getSettingsFieldsFor(string $name): array
    {
        $class = 'df\\flow\\mailingList\\adapter\\'.ucfirst($name);

        if (!class_exists($class)) {
            throw Exceptional::{'df/flow/mailingList/Setup,NotFound'}(
                'Mailing list adapter '.$name.' could not be found'
            );
        }

        return $class::getSettingsFields();
    }

    public static function getSettingsFields(): array
    {
        return static::SETTINGS_FIELDS;
    }

    public function getName(): string
    {
        $parts = explode('\\', get_class($this));
        return (string)array_pop($parts);
    }

    public function canConnect(): bool
    {
        return true;
    }
}
