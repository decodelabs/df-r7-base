<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Guidance;

use COM;
use DateTimeInterface;
use DecodeLabs\Exceptional;
use DecodeLabs\Guidance\Factory as FactoryInterface;
use DecodeLabs\Guidance\FactoryTrait;
use DecodeLabs\Guidance\Uuid;

class Factory implements FactoryInterface
{
    use FactoryTrait;

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

    /**
     * Create V1
     */
    public function createV1(
        int|string|null $node = null,
        ?int $clockSeq = null
    ): Uuid {
        if ($clockSeq === null) {
            $clockSeq = self::getMicrotime();
        }

        $clockSeq = substr(sprintf('%F', $clockSeq + self::INTERVAL), 0, -7);
        $clockSeq = base_convert($clockSeq, 10, 16);
        $clockSeq = pack('H*', str_pad($clockSeq, 16, '0', STR_PAD_LEFT));

        $uuid = $clockSeq[4] . $clockSeq[5] . $clockSeq[6] . $clockSeq[7] . $clockSeq[2] . $clockSeq[3] . $clockSeq[0] . $clockSeq[1];
        $uuid .= self::randomBytes(2);

        $uuid[8] = chr(ord($uuid[8]) & self::CLEAR_VARIANT | self::VARIANT_RFC);
        $uuid[6] = chr(ord($uuid[6]) & self::CLEAR_VERSION | self::VERSION_1);

        if ($node !== null) {
            if (is_int($node)) {
                $node = dechex($node);
            }

            $node = self::makeBin($node, 6);
        }

        if (!$node) {
            $node = self::randomBytes(6);
            $node[0] = pack('C', ord($node[0]) | 1);
        }

        $uuid .= $node;
        return new Uuid($uuid);
    }

    /**
     * Create V3
     */
    public function createV3(
        string $name,
        ?string $namespace = null
    ): Uuid {
        $namespace = self::makeBin($namespace, 16);

        if ($namespace === null) {
            $namespace = (string)$this->createV4();
        }

        $uuid = md5($namespace . $name, true);
        $uuid[8] = chr(ord($uuid[8]) & self::CLEAR_VARIANT | self::VARIANT_RFC);
        $uuid[6] = chr(ord($uuid[6]) & self::CLEAR_VERSION | self::VERSION_3);

        return new Uuid($uuid);
    }

    /**
     * Create V4
     */
    public function createV4(): Uuid
    {
        $uuid = self::randomBytes(16);
        $uuid[8] = chr(ord($uuid[8]) & self::CLEAR_VARIANT | self::VARIANT_RFC);
        $uuid[6] = chr(ord($uuid[6]) & self::CLEAR_VERSION | self::VERSION_4);

        return new Uuid($uuid);
    }

    /**
     * Create comb
     */
    public function createV4Comb(): Uuid
    {
        $uuid = self::randomBytes(8);
        $time = self::getMicrotime();

        $time = substr(sprintf('%F', $time + self::INTERVAL), 0, -7);
        $time = base_convert($time, 10, 16);
        $time = pack('H*', str_pad($time, 16, '0', STR_PAD_LEFT));

        $uuid .= $time[1] . $time[0] . $time[7] . $time[6] . $time[5] . $time[4] . $time[3] . $time[2];

        $uuid[8] = chr(ord($uuid[8]) & self::CLEAR_VARIANT | self::VARIANT_RESERVED);
        $uuid[6] = chr(ord($uuid[6]) & self::CLEAR_VERSION | self::VERSION_COMB);

        return new Uuid($uuid);
    }

    /**
     * Create V5
     */
    public function createV5(
        string $name,
        ?string $namespace = null
    ): Uuid {
        $namespace = self::makeBin($namespace, 16);

        if ($namespace === null) {
            $namespace = $this->createV4();
        }

        $uuid = md5($namespace . $name, true);
        $uuid[8] = chr(ord($uuid[8]) & self::CLEAR_VARIANT | self::VARIANT_RFC);
        $uuid[6] = chr(ord($uuid[6]) & self::CLEAR_VERSION | self::VERSION_5);

        return new Uuid($uuid);
    }


    /**
     * Create V6
     */
    public function createV6(
        int|string|null $node = null,
        ?int $clockSeq = null
    ): Uuid {
        throw Exceptional::Implementation(
            'V7 UUIDs are not yet supported'
        );
    }

    /**
     * Create V7
     */
    public function createV7(
        ?DateTimeInterface $date = null
    ): Uuid {
        throw Exceptional::Implementation(
            'V7 UUIDs are not yet supported'
        );
    }



    private static function getMicrotime(): float
    {
        $time = explode(' ', (string)microtime());
        $time[0] = substr($time[0], 2);
        return ((int)($time[1] . $time[0])) / 100;
    }

    private static function makeBin(
        ?string $string,
        int $length
    ): ?string {
        if ($string === null) {
            return null;
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


    public const RANDOM_URANDOM = 1;
    public const RANDOM_COM = 2;
    public const RANDOM_MT = 3;

    private static int $randomSource;
    private static mixed $randomGen;

    private static function randomBytes(
        int $bytes
    ): string {
        if (!isset(self::$randomSource)) {
            if (is_readable('/dev/urandom')) {
                self::$randomGen = fopen('/dev/urandom', 'rb');
                self::$randomSource = self::RANDOM_URANDOM;
            } elseif (class_exists('COM', false)) {
                try {
                    self::$randomGen = new \COM('CAPICOM.Utilities.1');
                    self::$randomSource = self::RANDOM_COM;
                } catch (\Throwable $e) {
                }
            }

            if (!isset(self::$randomSource)) {
                self::$randomSource = self::RANDOM_MT;
            }
        }

        $gen = self::$randomGen ?? null;

        switch (self::$randomSource) {
            case self::RANDOM_URANDOM:
                /**
                 * @var resource $gen
                 * @var int<0, max> $bytes
                 * */
                if (false === ($output = fread($gen, $bytes))) {
                    throw Exceptional::Runtime(
                        'Unable to read random bytes'
                    );
                }

                return $output;

            case self::RANDOM_COM:
                /**
                 * @var COM $gen
                 * @phpstan-ignore-next-line
                 */
                return base64_decode($gen->GetRandom($bytes, 0));

            case self::RANDOM_MT:
                $c = 0;
                $output = '';

                while ($c++ * 16 < $bytes) {
                    $output .= md5((string)mt_rand(), true);
                }

                return substr($output, 0, $bytes);

            default:
                throw Exceptional::Setup(
                    'Unable to generate random bytes'
                );
        }
    }
}
