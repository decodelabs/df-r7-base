<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\bucket;

use df;
use df\core;
use df\neon;
use df\flex;

class Base implements IBucket {

    use core\io\TAcceptTypeProcessor;

    const USER_SPECIFIC = false;
    const ALLOW_ONE_PER_USER = false;

    public static function loadAll() {
        foreach(df\Launchpad::$loader->lookupClassList('neon/bucket') as $name => $class) {
            try {
                $context = self::factory($name);
            } catch(InvalidArgumentException $e) {
                continue;
            }

            $output[$context->getName()] = $context;
        }

        ksort($output);
        return $output;
    }

    public static function getOptionsList() {
        $output = [];

        foreach(self::loadAll() as $name => $context) {
            $output[$name] = $context->getDisplayName();
        }

        return $output;
    }

    public static function factory($name) {
        if($name instanceof IBucket) {
            return $name;
        }

        $class = 'df\\neon\\bucket\\'.flex\Text::formatId($name);

        if(!class_exists($class)) {
            $class = __CLASS__;
        }

        return new $class();
    }

    public function __construct() {
        $this->setAcceptTypes($this->_acceptTypes);
    }

    public function getName() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function getDisplayName() {
        return flex\Text::formatName($this->getName());
    }


    public function isUserSpecific() {
        return (bool)static::USER_SPECIFIC;
    }

    public function allowOnePerUser() {
        return (bool)static::ALLOW_ONE_PER_USER;
    }
}