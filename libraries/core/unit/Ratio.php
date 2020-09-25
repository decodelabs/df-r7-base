<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\unit;

use df;
use df\core;

use DecodeLabs\Glitch\Dumpable;

class Ratio implements IRatio, Dumpable
{
    use core\TStringProvider;

    const FAREY_LIMIT = 100;

    protected $_numerator;
    protected $_denominator;

    public static function factory($value, $denominator=null)
    {
        if ($value instanceof IRatio) {
            return $value;
        }

        return new self($value, $denominator);
    }

    public function __construct($value, $denominator=null)
    {
        $this->parse($value, $denominator);
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function parse($value, $denominator=null)
    {
        if (false !== strpos($value, '/')) {
            $parts = explode('/', $value, 2);
            $value = trim((string)array_shift($parts));
            $denominator = trim((string)array_shift($parts));

            if (empty($denominator)) {
                $denominator = null;
            }
        }

        if ($denominator !== null) {
            return $this->setFraction($value, $denominator);
        }

        return $this->setFactor($value);
    }

    public function toString(): string
    {
        return $this->_numerator.'/'.$this->_denominator;
    }

    public function toCssString(): string
    {
        return $this->toString();
    }

    public function setFraction($numerator, $denominator)
    {
        while (floor($numerator) != $numerator) {
            $numerator *= 10;
            $denominator *= 10;
        }

        while (floor($denominator) != $denominator) {
            $numerator *= 10;
            $denominator *= 10;
        }

        list($this->_numerator, $this->_denominator) = self::reduce((int)$numerator, (int)$denominator);
        return $this;
    }

    public function getNumerator()
    {
        return $this->_numerator;
    }

    public function getDenominator()
    {
        return $this->_denominator;
    }

    public function setFactor($factor)
    {
        list($this->_numerator, $this->_denominator) = self::farey($factor, self::FAREY_LIMIT);

        $this->_numerator = (int)$this->_numerator;
        $this->_denominator = (int)$this->_denominator;

        return $this;
    }

    public function getFactor()
    {
        return $this->_numerator / $this->_denominator;
    }

    public static function reduce($numerator, $denominator)
    {
        if ((!($numerator % 1) && $numerator) && (!($denominator % 1) && $denominator)) {
            $high = max($numerator, $denominator);
            $low = min($numerator, $denominator);

            for ($i = $low; $i <= $high; ++$i) {
                if (!($numerator % $i) && !($denominator % $i) && $i) {
                    $numerator /= $i;
                    $denominator /= $i;
                }
            }

            if (abs($numerator) !== $numerator && abs($denominator) !== $denominator) {
                $numerator = abs($numerator);
                $denominator = abs($denominator);
            }
        }

        return [$numerator, $denominator];
    }

    public static function farey($factor, $limit)
    {
        $factor = (double)$factor;
        $limit = (int)$limit;

        if ($factor < 0) {
            $output = self::farey(-$factor, $limit);
            return [-$output[0], $output[1]];
        }

        $z = $limit - $limit;
        $lower = [$z, $z + 1];
        $upper = [$z + 1, $z];

        while (true) {
            $mediant = [$lower[0] + $upper[0], $lower[1] + $upper[1]];
            $crossFactor = $factor * $mediant[1];

            if ($crossFactor > $mediant[0]) {
                if ($limit < $mediant[1]) {
                    return $upper;
                }

                $lower = $mediant;
            } elseif ($crossFactor == $mediant) {
                if ($limit >= $mediant[1]) {
                    return $mediant;
                }

                if ($lower[1] < $upper[1]) {
                    return $lower;
                }

                return $upper;
            } else {
                if ($limit < $mediant[1]) {
                    return $lower;
                }

                $upper = $mediant;
            }
        }
    }

    /**
     * Inspect for Glitch
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->toString();
    }
}
