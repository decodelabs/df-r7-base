<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\mmdb;

use DecodeLabs\Compass\Ip;

interface IReader
{
    public function get(
        Ip|string $ip
    );
}


interface IDecoder
{
    public function decode($offset = null);
}

interface IDataTypes
{
    public const T_EXTENDED = 0;
    public const T_POINTER = 1;
    public const T_UTF8_STRING = 2;
    public const T_DOUBLE = 3;
    public const T_BYTES = 4;
    public const T_UINT16 = 5;
    public const T_UINT32 = 6;
    public const T_MAP = 7;
    public const T_INT32 = 8;
    public const T_UINT64 = 9;
    public const T_UINT128 = 10;
    public const T_ARRAY = 11;
    public const T_CONTAINER = 12;
    public const T_END_MARKER = 13;
    public const T_BOOLEAN = 14;
    public const T_FLOAT = 15;
}
