<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\neon\bucket;

use DecodeLabs\Dictum;

use DecodeLabs\R7\Legacy;
use df\core;

class Base implements IBucket
{
    use core\lang\TAcceptTypeProcessor;

    public const USER_SPECIFIC = false;
    public const ALLOW_ONE_PER_USER = false;

    public static function loadAll()
    {
        $output = [];

        foreach (Legacy::getLoader()->lookupClassList('neon/bucket') as $name => $class) {
            $context = self::factory($name);
            $output[$context->getName()] = $context;
        }

        ksort($output);
        return $output;
    }

    public static function getOptionsList()
    {
        $output = [];

        foreach (self::loadAll() as $name => $context) {
            $output[$name] = $context->getDisplayName();
        }

        return $output;
    }

    public static function factory($name)
    {
        if ($name instanceof IBucket) {
            return $name;
        }

        $class = 'df\\neon\\bucket\\' . Dictum::id($name);

        if (!class_exists($class)) {
            $class = __CLASS__;
        }

        return new $class();
    }

    public function __construct()
    {
        $this->setAcceptTypes(...$this->_acceptTypes);
    }

    public function getName(): string
    {
        $parts = explode('\\', get_class($this));
        return (string)array_pop($parts);
    }

    public function getDisplayName(): string
    {
        return Dictum::name($this->getName());
    }


    public function isUserSpecific()
    {
        return (bool)static::USER_SPECIFIC;
    }

    public function allowOnePerUser()
    {
        return (bool)static::ALLOW_ONE_PER_USER;
    }
}
