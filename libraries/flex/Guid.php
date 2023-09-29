<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\core;
use df\flex;

class Guid implements IGuid, Dumpable
{
    use core\TStringProvider;

    public const CLEAR_VERSION = 15;
    public const CLEAR_VARIANT = 63;

    public const VARIANT_RESERVED = 224;
    public const VARIANT_MS = 192;
    public const VARIANT_RFC = 128;
    public const VARIANT_NCS = 0;

    public const VERSION_1 = 16;
    public const VERSION_3 = 48;
    public const VERSION_4 = 64;
    public const VERSION_5 = 80;
    public const VERSION_COMB = 0xc0;

    public const INTERVAL = 0x01b21dd213814000;

    protected $_bytes;


    public static function shorten(string $id): string
    {
        $bytes = static::factory($id)->getBytes();
        $hex = bin2hex($bytes);
        return Dictum::baseConvert($hex, 16, 62);
    }

    public static function unshorten(string $id): string
    {
        $length = strlen($id);

        // Full
        if ($length === 36) {
            return $id;
        }

        // Short
        if ($length >= 20 && $length <= 22) {
            $hex = Dictum::baseConvert($id, 62, 16);
            $hex = str_pad($hex, 32, '0', \STR_PAD_LEFT);
            return (string)flex\Guid::factory($hex);
        }

        throw Exceptional::InvalidArgument(
            'Unable to unshorten ID'
        );
    }






    public static function uuid1($node = null, $time = null)
    {
        if ($time === null) {
            $time = self::_getMicrotime();
        }

        $time = substr(sprintf('%F', $time + self::INTERVAL), 0, -7);
        $time = base_convert($time, 10, 16);
        $time = pack('H*', str_pad($time, 16, '0', STR_PAD_LEFT));

        $uuid = $time[4] . $time[5] . $time[6] . $time[7] . $time[2] . $time[3] . $time[0] . $time[1];
        $uuid .= Generator::randomBytes(2);

        $uuid[8] = chr(ord($uuid[8]) & self::CLEAR_VARIANT | self::VARIANT_RFC);
        $uuid[6] = chr(ord($uuid[6]) & self::CLEAR_VERSION | self::VERSION_1);

        if ($node !== null) {
            $node = self::_makeBin($node, 6);
        }

        if (!$node) {
            $node = Generator::randomBytes(6);
            $node[0] = pack('C', ord($node[0]) | 1);
        }

        $uuid .= $node;
        return new self($uuid);
    }

    public static function uuid3($name, $namespace = null)
    {
        $namespace = self::_makeBin($namespace, 16);

        if ($namespace === null) {
            $namespace = self::uuid4();
        }

        $uuid = md5($namespace . $name, true);
        $uuid[8] = chr(ord($uuid[8]) & self::CLEAR_VARIANT | self::VARIANT_RFC);
        $uuid[6] = chr(ord($uuid[6]) & self::CLEAR_VERSION | self::VERSION_3);

        return new self($uuid);
    }

    public static function uuid4()
    {
        $uuid = Generator::randomBytes(16);
        $uuid[8] = chr(ord($uuid[8]) & self::CLEAR_VARIANT | self::VARIANT_RFC);
        $uuid[6] = chr(ord($uuid[6]) & self::CLEAR_VERSION | self::VERSION_4);

        return new self($uuid);
    }

    public static function uuid5($name, $namespace = null)
    {
        $namespace = self::_makeBin($namespace, 16);

        if ($namespace === null) {
            $namespace = self::uuid4();
        }

        $uuid = md5($namespace . $name, true);
        $uuid[8] = chr(ord($uuid[8]) & self::CLEAR_VARIANT | self::VARIANT_RFC);
        $uuid[6] = chr(ord($uuid[6]) & self::CLEAR_VERSION | self::VERSION_5);

        return new self($uuid);
    }

    public static function comb()
    {
        $uuid = Generator::randomBytes(8);
        $time = self::_getMicrotime();

        $time = substr(sprintf('%F', $time + self::INTERVAL), 0, -7);
        $time = base_convert($time, 10, 16);
        $time = pack('H*', str_pad($time, 16, '0', STR_PAD_LEFT));

        $uuid .= $time[1] . $time[0] . $time[7] . $time[6] . $time[5] . $time[4] . $time[3] . $time[2];

        $uuid[8] = chr(ord($uuid[8]) & self::CLEAR_VARIANT | self::VARIANT_RESERVED);
        $uuid[6] = chr(ord($uuid[6]) & self::CLEAR_VERSION | self::VERSION_COMB);

        return new self($uuid);
    }

    private static function _getMicrotime()
    {
        $time = explode(' ', (string)microtime());
        $time[0] = substr($time[0], 2);
        return ((int)($time[1] . $time[0])) / 100;
    }

    private static function _makeBin($string, $length)
    {
        if ($string === null) {
            return null;
        }

        if ($string instanceof IGuid) {
            return $string->getBytes();
        }

        $string = (string)$string;

        if (strlen($string) == $length) {
            return $string;
        }

        $string = (string)preg_replace('/^urn:uuid:/is', '', $string);
        $string = (string)preg_replace('/[^a-f0-9]/is', '', $string);

        if (strlen($string) != ($length * 2)) {
            return null;
        }

        return pack('H*', $string);
    }

    public static function void()
    {
        return new self('0000000000000000');
    }

    public static function factory($uuid)
    {
        if ($uuid instanceof IGuid) {
            return $uuid;
        }

        return new self(self::_makeBin($uuid, 16));
    }

    public static function isValidString($uuid)
    {
        if ($uuid instanceof IGuid) {
            return true;
        }

        return preg_match('/^[a-f0-9]{32}|[a-f0-9\-]{36}$/i', $uuid);
    }


    public function __construct($bytes)
    {
        if (strlen((string)$bytes) != 16) {
            throw Exceptional::InvalidArgument(
                'Guid must be a 128 bit integer'
            );
        }

        $this->_bytes = $bytes;
    }

    public function getBytes()
    {
        return $this->_bytes;
    }

    public function getHex()
    {
        return bin2hex($this->_bytes);
    }

    public function getUrn()
    {
        return 'urn:uuid:' . $this->toString();
    }

    public function getVersion()
    {
        return ord($this->_bytes[6]) >> 4;
    }

    public function getVariant()
    {
        $byte = ord($this->_bytes[8]);

        if ($byte >= self::VARIANT_RESERVED) {
            return self::VARIANT_RESERVED;
        } elseif ($byte >= self::VARIANT_MS) {
            return self::VARIANT_MS;
        } elseif ($byte >= self::VARIANT_RFC) {
            return self::VARIANT_RFC;
        } else {
            return self::VARIANT_NCS;
        }
    }

    public function getVariantName()
    {
        switch ($this->getVariant()) {
            case self::VARIANT_RESERVED:
                return 'Reserved';

            case self::VARIANT_MS:
                return 'MS';

            case self::VARIANT_RFC:
                return 'RFC4122';

            case self::VARIANT_NCS:
                return 'NCS';
        }
    }

    public function getNode()
    {
        if (ord($this->_bytes[6]) >> 4 != 1) {
            return null;
        }

        return bin2hex(substr($this->_bytes, 10));
    }

    public function getTime()
    {
        if (ord($this->_bytes[6]) >> 4 != 1) {
            return null;
        }

        $time = bin2hex(
            $this->_bytes[6] .
            $this->_bytes[7] .
            $this->_bytes[4] .
            $this->_bytes[5] .
            $this->_bytes[0] .
            $this->_bytes[1] .
            $this->_bytes[2] .
            $this->_bytes[3]
        );

        $time[0] = '0';

        return (hexdec($time) - self::INTERVAL) / 1000000;
    }

    public function toString(): string
    {
        return
            bin2hex(substr($this->_bytes, 0, 4)) . '-' .
            bin2hex(substr($this->_bytes, 4, 2)) . '-' .
            bin2hex(substr($this->_bytes, 6, 2)) . '-' .
            bin2hex(substr($this->_bytes, 8, 2)) . '-' .
            bin2hex(substr($this->_bytes, 10, 6));
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->toString();
        yield 'meta:version' => $this->getVersion();
        yield 'meta:variant' => $this->getVariantName();
    }
}
