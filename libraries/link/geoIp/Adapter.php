<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\geoIp;

use df\link\Ip;

interface Adapter
{
    public static function fromConfig(): Adapter;
    public static function isAvailable(): bool;

    public function getName(): string;
    public function lookup(Ip $ip, Result $result): Result;
}
