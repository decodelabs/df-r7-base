<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flow\mailingList\adapter;

use df;
use df\core;
use df\flow;

abstract class Base implements flow\mailingList\IAdapter {

    const SETTINGS_FIELDS = [];

    public static function factory(core\collection\ITree $options) {
        if(!$name = $options['adapter']) {
            throw core\Error::{'flow/mailingList/ESetup'}(
                'Mailing list adapter name has not been set in config'
            );
        }

        $class = 'df\\flow\\mailingList\\adapter\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw core\Error::{'flow/mailingList/ESetup,ENotFound'}(
                'Mailing list adapter '.$name.' could not be found'
            );
        }

        return new $class($options);
    }

    public static function getSettingsFieldsFor($name) {
        $class = 'df\\flow\\mailingList\\adapter\\'.ucfirst($name);

        if(!class_exists($class)) {
            throw core\Error::{'flow/mailingList/ESetup,ENotFound'}(
                'Mailing list adapter '.$name.' could not be found'
            );
        }

        return $class::getSettingsFields();
    }

    public static function getSettingsFields() {
        return static::SETTINGS_FIELDS;
    }

    protected function __construct(core\collection\ITree $options) {}

    public function getName(): string {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function canConnect(): bool {
        return true;
    }
}
