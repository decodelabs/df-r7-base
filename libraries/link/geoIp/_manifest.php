<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\geoIp;

use df;
use df\core;
use df\link;

// Exceptions
interface IException
{
}
class RuntimeException extends \RuntimeException implements IException
{
}


// Interfaces
interface IHandler
{
    public static function isAdapterAvailable($name);
    public static function getAdapterList();
    public static function getAvailableAdapterList();
    public function getAdapter();
    public function lookup($ip);
}

interface IAdapter
{
    public static function fromConfig();
    public static function isAvailable();
    public function getName(): string;
    public function lookup(link\Ip $ip, Result $result);
}

trait TAdapter
{
    public function getName(): string
    {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }
}
