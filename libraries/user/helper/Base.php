<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\helper;

use df;
use df\core;
use df\user;

use DecodeLabs\Exceptional;

abstract class Base implements user\IHelper
{
    public $manager;

    public static function factory(user\IManager $manager, $name)
    {
        $class = 'df\\user\\helper\\'.ucfirst($name);

        if (!class_exists($class)) {
            throw Exceptional::Runtime(
                'User helper '.$name.' could not be found'
            );
        }

        return new $class($manager);
    }

    public function __construct(user\IManager $manager)
    {
        $this->manager = $manager;
    }

    public function getManager()
    {
        return $this->manager;
    }

    public function getHelperName()
    {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }
}
