<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex;

use DecodeLabs\Dictum\Text;

abstract class Generator implements IGenerator
{
    public static function random($minLength = 6, $maxLength = 14, $additionalChars = null)
    {
        $characters = new Text('abcdefghijklmnopqrstuvwxyz');

        if ($additionalChars !== null) {
            $characters->append($additionalChars);
        }

        return self::_generateRandom($characters, $minLength, $maxLength);
    }

    public static function randomNumber($minLength = 6, $maxLength = 14)
    {
        $characters = new Text('0123456789');
        return self::_generateRandom($characters, $minLength, $maxLength);
    }

    private static function _generateRandom(Text $characters, $minLength, $maxLength)
    {
        if (!is_int($minLength)) {
            $minLength = 4;
        }

        if (!is_int($maxLength)) {
            $maxLength = 32;
        }

        if ($maxLength < $minLength) {
            $maxLength = $minLength;
        }

        mt_srand((int)(microtime(true) * 1000000));

        $output = '';
        $length = mt_rand($minLength, $maxLength);
        $count = count($characters);

        for ($i = 0; $i < $length; $i++) {
            if ($i == 0 || mt_rand(0, 3) < 2) {
                $nextChar = $characters[mt_rand(0, $count - 1)];

                if (mt_rand(0, 1)) {
                    $nextChar = mb_strtoupper($nextChar);
                }
            } else {
                $nextChar = mt_rand(0, 9);
            }

            $output .= $nextChar;
        }

        return (string)$output;
    }

    public static function passKey()
    {
        return self::random(10, 20, '!£$%^&*()_-+=#'); // @ignore-non-ascii
    }

    public static function sessionId($raw = false)
    {
        $output = self::passKey();

        for ($i = 0; $i < 32; $i++) {
            $output .= mt_rand();
        }

        return sha1(uniqid($output, true), $raw);
    }


    public const RANDOM_URANDOM = 1;
    public const RANDOM_COM = 2;
    public const RANDOM_MT = 3;

    private static $_randomSource;
    private static $_randomGen;

    public static function randomBytes($bytes)
    {
        if (!isset(self::$_randomSource)) {
            if (is_readable('/dev/urandom')) {
                self::$_randomGen = fopen('/dev/urandom', 'rb');
                self::$_randomSource = self::RANDOM_URANDOM;
            } elseif (class_exists('COM', false)) {
                try {
                    self::$_randomGen = new \COM('CAPICOM.Utilities.1');
                    self::$_randomSource = self::RANDOM_COM;
                } catch (\Throwable $e) {
                }
            }

            if (!isset(self::$_randomSource)) {
                self::$_randomSource = self::RANDOM_MT;
            }
        }

        switch (self::$_randomSource) {
            case self::RANDOM_URANDOM:
                return fread(self::$_randomGen, $bytes);

            case self::RANDOM_COM:
                return base64_decode(self::$_randomGen->GetRandom($bytes, 0));

            case self::RANDOM_MT:
                $c = 0;
                $output = '';

                while ($c++ * 16 < $bytes) {
                    $output .= md5((string)mt_rand(), true);
                }

                return substr($output, 0, $bytes);
        }
    }


    // UUID
    public static function uuid1($node = null, $time = null)
    {
        return Guid::uuid1($node, $time)->toString();
    }

    public static function uuid3($name, $namespace = null)
    {
        return Guid::uuid3($name, $namespace)->toString();
    }

    public static function uuid4()
    {
        return Guid::uuid4()->toString();
    }

    public static function uuid5($name, $namespace = null)
    {
        return Guid::uuid5($name, $namespace)->toString();
    }

    public static function combGuid()
    {
        return Guid::comb()->toString();
    }
}
