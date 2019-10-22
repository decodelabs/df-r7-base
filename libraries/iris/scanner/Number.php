<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\scanner;

use df;
use df\core;
use df\iris;
use df\flex;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Number implements iris\IScanner, Inspectable
{
    protected $_allowHex = true;
    protected $_allowOctal = true;
    protected $_allowENotation = true;
    protected $_allowSuffixes = true;

    public function __construct()
    {
    }

    public function getName(): string
    {
        return 'Number';
    }

    public function getWeight()
    {
        return 50;
    }

    public function shouldAllowHex(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_allowHex = $flag;
            return $this;
        }

        return $this->_allowHex;
    }

    public function shouldAllowOctal(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_allowOctal = $flag;
            return $this;
        }

        return $this->_allowOctal;
    }

    public function shouldAllowENotation(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_allowENotation = $flag;
            return $this;
        }

        return $this->_allowENotation;
    }

    public function shouldAllowSuffixes(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_allowSuffixes = $flag;
            return $this;
        }

        return $this->_allowSuffixes;
    }


    public function initialize(iris\Lexer $lexer)
    {
    }

    public function check(iris\Lexer $lexer)
    {
        return flex\Text::isDigit($lexer->char)
            || ($lexer->char == '.' && flex\Text::isDigit($lexer->peek(1, 1)));
    }

    public function run(iris\Lexer $lexer)
    {
        if ($lexer->char == '0') {
            $peek = $lexer->peek(1, 1);

            if (strtolower($peek) == 'x') {
                // Hex
                if (!$this->_allowHex) {
                    throw new iris\UnexpectedCharacterException(
                        'Hex numbers are not allowed',
                        $lexer->getLocation()
                    );
                }

                $lexer->extract(2);
                $string = $lexer->extractRegexRange('a-fA-F0-9');

                return $lexer->newToken('literal/integer', flex\Text::baseConvert($string, 16, 10));
            } elseif (flex\Text::isDigit($peek)) {
                // Octal
                if (!$this->_allowOctal) {
                    throw new iris\UnexpectedCharacterException(
                        'Octal numbers are not allowed',
                        $lexer->getLocation()
                    );
                }

                $lexer->extract(1);
                $string = $lexer->extractRegexRange('0-7');

                return $lexer->newToken('literal/integer', flex\Text::baseConvert($string, 8, 10));
            }
        }

        $fraction = null;
        $exponent = null;

        $whole = '0';

        if ($lexer->char != '.') {
            $whole = $lexer->extractNumeric();
        }

        if ($lexer->char == '.' && flex\Text::isDigit($lexer->peek(1, 1))) {
            $lexer->extract();
            $fraction = $lexer->extractNumeric();
        }

        $number = $whole;
        $scale = 0;

        if ($fraction) {
            $scale = strlen($fraction);
            $number .= '.'.$fraction;
        }

        if (strtolower($lexer->char) == 'e') {
            // E notation
            if (!$this->_allowENotation) {
                throw new iris\UnexpectedCharacterException(
                    'E notation numbers are not allowed'
                );
            }

            $lexer->extract();
            $exponent = (int)$lexer->extractRegexRange('0-9\-\+');

            $powScale = 0;

            if ($exponent < 0) {
                $scale -= $exponent;
                $powScale = -1 * $exponent;
            }

            if ($scale < 0 || $exponent >= 0) {
                $scale = 0;
            }

            $number = bcmul($number, bcpow(10, $exponent, $powScale), $scale);
        }

        $floatSuffix = false;
        $decimalSuffix = false;
        $next = strtolower($lexer->char);

        if ($next == 'f') {
            if (!$this->_allowSuffixes) {
                throw new iris\UnexpectedCharacterException(
                    'Float suffixes are not allowed',
                    $lexer->getLocation()
                );
            }

            $lexer->extract();
            $floatSuffix = true;
        } elseif ($next == 'd') {
            if (!$this->_allowSuffixes) {
                throw new iris\UnexpectedCharacterException(
                    'Decimal suffixes are not allowed',
                    $lexer->getLocation()
                );
            }

            $lexer->extract();
            $decimalSuffix = true;
        }

        if ($fraction !== null && !$decimalSuffix) {
            $floatSuffix = true;
        }

        if ($floatSuffix) {
            return $lexer->newToken('literal/float', $number);
        }

        if ($decimalSuffix) {
            return $lexer->newToken('literal/decimal', $number);
        }

        return $lexer->newToken('literal/integer', $whole);
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setProperties([
                '*hex' => $inspector($this->_allowHex),
                '*octal' => $inspector($this->_allowOctal),
                '*e-notation' => $inspector($this->_allowENotation),
                '*suffixes' => $inspector($this->_allowSuffixes)
            ]);
    }
}
