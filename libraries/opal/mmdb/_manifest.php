<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\mmdb;

use df;
use df\core;
use df\opal;

interface IReader
{
    public function get($ip);
}


interface IDecoder
{
    public function decode($offset=null);
}

interface IDataTypes
{
    const T_EXTENDED = 0;
    const T_POINTER = 1;
    const T_UTF8_STRING = 2;
    const T_DOUBLE = 3;
    const T_BYTES = 4;
    const T_UINT16 = 5;
    const T_UINT32 = 6;
    const T_MAP = 7;
    const T_INT32 = 8;
    const T_UINT64 = 9;
    const T_UINT128 = 10;
    const T_ARRAY = 11;
    const T_CONTAINER = 12;
    const T_END_MARKER = 13;
    const T_BOOLEAN = 14;
    const T_FLOAT = 15;
}
