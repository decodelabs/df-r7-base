<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\math;

use df;
use df\core;

class Util
{
    public static function clampFloat(?float $number, float $min, float $max): ?float
    {
        if ($number === null) {
            return null;
        }

        return max($min, min($max, $number));
    }

    public static function clampInt(?int $number, int $min, int $max): ?int
    {
        if ($number === null) {
            return null;
        }

        return max($min, min($max, $number));
    }

    public static function clampDegrees(?float $degrees, float $min=null, float $max=null): ?float
    {
        if ($degrees === null) {
            return null;
        }

        while ($degrees < 0) {
            $degrees += 360;
        }

        while ($degrees > 359) {
            $degrees -= 360;
        }

        if ($min !== null) {
            $degrees = max($min, $degrees);
        }

        if ($max !== null) {
            $degrees = min($max, $degrees);
        }

        return $degrees;
    }
}
