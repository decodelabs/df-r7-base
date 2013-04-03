<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\latex\package;

use df;
use df\core;
use df\flex;
use df\iris;
    
abstract class Base extends iris\processor\Base implements iris\IPackage {

    protected static $_commands = [];

    public static function getCommandList() {
        return static::$_commands;
    }
}