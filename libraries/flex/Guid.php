<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Guidance;
use DecodeLabs\Guidance\Format;
use df\core;

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
        return Guidance::shorten($id, Format::GmpBase62);
    }

    public static function unshorten(string $id): string
    {
        return (string)Guidance::fromShortString($id, Format::GmpBase62);
    }



    public static function uuid1($node = null, $time = null)
    {
        return new self(
            Guidance::createV1($node, $time)->getBytes()
        );
    }

    public static function uuid3($name, $namespace = null)
    {
        return new self(
            Guidance::createV3($name, $namespace)->getBytes()
        );
    }

    public static function uuid4()
    {
        return new self(
            Guidance::createV4()->getBytes()
        );
    }

    public static function uuid5($name, $namespace = null)
    {
        return new self(
            Guidance::createV5($name, $namespace)->getBytes()
        );
    }

    public static function comb()
    {
        return new self(
            Guidance::createV4Comb()->getBytes()
        );
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

        return preg_match('/^[a-f0-9]{32}|[a-f0-9\-]{36}$/i', (string)$uuid);
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
