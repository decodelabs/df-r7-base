<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\app\runner;

use df;
use df\core;

use DecodeLabs\Exceptional;

abstract class Base implements core\IRunner
{
    protected $_isRunning = false;

    public static function factory(string $name): core\IRunner
    {
        $class = 'df\\core\\app\\runner\\'.$name;

        if (!class_exists($class)) {
            throw Exceptional::NotFound(
                'Runner '.$name.' could not be found'
            );
        }

        return new $class();
    }
}
