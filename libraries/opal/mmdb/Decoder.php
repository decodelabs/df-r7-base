<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\mmdb;

use df;
use df\core;
use df\opal;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\File;
use DecodeLabs\Exceptional;

class Decoder implements IDecoder
{
    const POINTER_VALUE_OFFSET = [
        1 => 0,
        2 => 2048,
        3 => 526336,
        4 => 0
    ];

    protected $_file;
    protected $_pointerBase = 0;
    protected $_isLittleEndian = false;

    public function __construct(File $file, $pointerBase=0)
    {
        $this->_file = $file;
        $this->_pointerBase = (int)$pointerBase;
        $this->_isLittleEndian = $this->_isPlatformLittleEndian();
    }

    public function decode($offset=null)
    {
        if ($offset === null) {
            $offset = $this->_pointerBase;
        }

        list(, $ctrlByte) = unpack(
            'C',
            $this->_file->readFrom($offset, 1)
        );

        $offset++;
        $type = $ctrlByte >> 5;

        if ($type === IDataTypes::T_POINTER) {
            list($pointer, $offset) = $this->_decodePointer($ctrlByte, $offset);
            list($result) = $this->decode($pointer);
            return [$result, $offset];
        }

        if ($type === IDataTypes::T_EXTENDED) {
            list(, $nextByte) = unpack(
                'C',
                $this->_file->readFrom($offset, 1)
            );

            $type = $nextByte + 7;
            $offset++;

            if ($type < 8) {
                throw Exceptional::UnexpectedValue(
                    'Decoder error - extended type resolved to type < 8'
                );
            }
        }

        list($size, $offset) = $this->_getSizeFromCtrlByte($ctrlByte, $offset);

        switch ($type) {
            case IDataTypes::T_MAP:
                return $this->_decodeMap($size, $offset);

            case IDataTypes::T_ARRAY:
                return $this->_decodeArray($size, $offset);

            case IDataTypes::T_BOOLEAN:
                return [$this->_decodeBoolean($size), $offset];
        }

        $newOffset = $offset + $size;
        $bytes = $this->_file->readFrom($offset, $size);

        switch ($type) {
            case IDataTypes::T_UTF8_STRING:
                return [$this->_decodeString($bytes), $newOffset];

            case IDataTypes::T_DOUBLE:
                $this->_verifySize(8, $size);
                return [$this->_decodeDouble($bytes), $newOffset];

            case IDataTypes::T_FLOAT:
                $this->_verifySize(4, $size);
                return [$this->_decodeFloat($bytes), $newOffset];

            case IDataTypes::T_BYTES:
                return [$bytes, $newOffset];

            case IDataTypes::T_UINT16:
                return [$this->_decodeUint16($bytes), $newOffset];

            case IDataTypes::T_UINT32:
                return [$this->_decodeUint32($bytes), $newOffset];

            case IDataTypes::T_INT32:
                return [$this->_decodeInt32($bytes), $newOffset];

            case IDataTypes::T_UINT64:
                return [$this->_decodeUint64($bytes), $newOffset];

            case IDataTypes::T_UINT128:
                return [$this->_decodeUint128($bytes), $newOffset];

            default:
                throw Exceptional::UnexpectedValue(
                    'Unknown type: '.$type
                );
        }
    }


    // Array
    protected function _decodeArray($size, $offset)
    {
        $output = [];

        for ($i = 0; $i < $size; $i++) {
            list($value, $offset) = $this->decode($offset);
            $output[] = $value;
        }

        return [$output, $offset];
    }

    // Boolean
    protected function _decodeBoolean($size)
    {
        return $size == 0 ? false : true;
    }

    // Double
    protected function _decodeDouble($bits)
    {
        list(, $output) = unpack('d', $this->_switchByteOrder($bits));
        return $output;
    }

    // Float
    protected function _decodeFloat($bits)
    {
        list(, $output) = unpack('f', $this->_switchByteOrder($bits));
        return $output;
    }

    // Int32
    protected function _decodeInt32($bytes)
    {
        $bytes = $this->_zeroPadLeft($bytes, 4);
        list(, $output) = unpack('l', $this->_switchByteOrder($bytes));
        return $output;
    }

    // Map
    protected function _decodeMap($size, $offset)
    {
        $output = [];

        for ($i = 0; $i < $size; $i++) {
            list($key, $offset) = $this->decode($offset);
            list($value, $offset) = $this->decode($offset);
            $output[$key] = $value;
        }

        return [$output, $offset];
    }

    // Pointer
    protected function _decodePointer($ctrlByte, $offset)
    {
        $pointerSize = (($ctrlByte >> 3) & 0x3) + 1;
        $buffer = $this->_file->readFrom($offset, $pointerSize);
        $offset += $pointerSize;

        $packed = $pointerSize == 4 ?
            $buffer :
            (pack('C', $ctrlByte & 0x7)).$buffer;

        $unpacked = $this->_decodeUint32($packed);
        $pointer = $unpacked + $this->_pointerBase + self::POINTER_VALUE_OFFSET[$pointerSize];

        return [$pointer, $offset];
    }

    // Uint16
    protected function _decodeUint16($bytes)
    {
        return $this->_decodeUint32($bytes);
    }

    // Uint32
    protected function _decodeUint32($bytes)
    {
        list(, $int) = unpack('N', $this->_zeroPadLeft($bytes, 4));
        return $int;
    }

    // Uint64
    protected function _decodeUint64($bytes)
    {
        return $this->_decodeBigUint($bytes, 8);
    }

    // Uint128
    protected function _decodeUint128($bytes)
    {
        return $this->_decodeBigUint($bytes, 16);
    }

    // Big Uint
    protected function _decodeBigUint($bytes, $size)
    {
        $longs = $size / 4;
        $output = 0;
        $bytes = $this->_zeroPadLeft($bytes, $size);
        $unpacked = array_merge(unpack('N'.$longs, $bytes));

        foreach ($unpacked as $part) {
            $output = bcadd(bcmul((string)$output, bcpow('2', '32')), $part);
        }

        return $output;
    }

    // String
    protected function _decodeString($bytes)
    {
        return $bytes;
    }


    // Helpers
    protected function _getSizeFromCtrlByte($ctrlByte, $offset)
    {
        $size = $ctrlByte & 0x1f;
        $bytesToRead = $size < 29 ? 0 : $size - 28;
        $bytes = $this->_file->readFrom($offset, $bytesToRead);
        $decoded = $this->_decodeUint32($bytes);

        if ($size == 29) {
            $size = 29 + $decoded;
        } elseif ($size == 30) {
            $size = 285 + $decoded;
        } elseif ($size > 30) {
            $size = ($decoded & (0x0FFFFFFF >> (32 - (8 * $bytesToRead)))) + 65821;
        }

        return [$size, $offset + $bytesToRead];
    }

    protected function _zeroPadLeft($content, $length)
    {
        return str_pad($content, $length, "\x00", \STR_PAD_LEFT);
    }

    protected function _switchByteOrder($bytes)
    {
        return $this->_isLittleEndian ? strrev($bytes) : $bytes;
    }

    protected function _isPlatformLittleEndian()
    {
        $testInt = 0x00FF;
        $packed = pack('S', $testInt);
        return $testInt === current(unpack('v', $packed));
    }

    protected function _verifySize($expected, $actual)
    {
        if ($expected != $actual) {
            throw Exceptional::UnexpectedValue(
                'Data size incorrect - read '.$actual.', expected '.$expected
            );
        }
    }
}
