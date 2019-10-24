<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\app\runner;

use df;
use df\core;

use DecodeLabs\Glitch;

abstract class Base implements core\IRunner
{
    protected $_isRunning = false;
    protected $_dispatchException;


    public static function factory(string $name): core\IRunner
    {
        $class = 'df\\core\\app\\runner\\'.$name;

        if (!class_exists($class)) {
            throw Glitch::ENotFound('Runner '.$name.' could not be found');
        }

        return new $class();
    }


    // Dispatch
    public function getDispatchException(): ?\Throwable
    {
        return $this->_dispatchException;
    }
}
