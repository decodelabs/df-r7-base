<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\scanner;

use df;
use df\core;
use df\iris;
    
class Number implements iris\IScanner, core\IDumpable {

    protected $_allowHex = true;
    protected $_allowOctal = true;
    protected $_allowENotation = true;
    protected $_allowSuffixes = true;

    public function __construct() {

    }

    public function getName() {
        return 'Number';
    }

    public function getWeight() {
        return 50;
    }

    public function shouldAllowHex($flag=null) {
        if($flag !== null) {
            $this->_allowHex = (bool)$flag;
            return $this;
        }

        return $this->_allowHex;
    }

    public function shouldAllowOctal($flag=null) {
        if($flag !== null) {
            $this->_allowOctal = (bool)$flag;
            return $this;
        }

        return $this->_allowOctal;
    }

    public function shouldAllowENotation($flag=null) {
        if($flag !== null) {
            $this->_allowENotation = (bool)$flag;
            return $this;
        }

        return $this->_allowENotation;
    }

    public function shouldAllowSuffixes($flag=null) {
        if($flag !== null) {
            $this->_allowSuffixes = (bool)$flag;
            return $this;
        }

        return $this->_allowSuffixes;
    }


    public function initialize(iris\ILexer $lexer) {

    }

    public function check(iris\ILexer $lexer) {
        return core\string\Util::isDigit($lexer->char)
            || ($lexer->char == '.' && core\string\Util::isDigit($lexer->peek(1, 1)));
    }

    public function run(iris\ILexer $lexer) {
        if($lexer->char == '0') {
            $peek = $lexer->peek(1, 1);

            if(strtolower($peek) == 'x') {
                // Hex
                if(!$this->_allowHex) {
                    throw new iris\UnexpectedCharacterException(
                        'Hex numbers are not allowed',
                        $lexer->getLocation()
                    );
                }

                $lexer->extract(2);
                $string = $lexer->extractRegexRange('a-fA-F0-9');

                return $lexer->newToken('literal/integer', core\string\Manipulator::baseConvert($string, 16, 10));
            } else if(core\string\Util::isDigit($peek)) {
                // Octal
                if(!$this->_allowOctal) {
                    throw new iris\UnexpectedCharacterException(
                        'Octal numbers are not allowed',
                        $lexer->getLocation()
                    );
                }

                $lexer->extract(1);
                $string = $lexer->extractRegexRange('0-7');

                return $lexer->newToken('literal/integer', core\string\Manipulator::baseConvert($string, 8, 10));
            }
        }

        $fraction = null;
        $exponent = null;

        $whole = '0';

        if($lexer->char != '.') {
            $whole = $lexer->extractNumeric();
        }

        if($lexer->char == '.' && core\string\Util::isDigit($lexer->peek(1, 1))) {
            $lexer->extract();
            $fraction = $lexer->extractNumeric();
        }

        $number = $whole;
        $scale = 0;

        if($fraction) {
            $scale = strlen($fraction);
            $number .= '.'.$fraction;
        }

        if(strtolower($lexer->char) == 'e') {
            // E notation
            if(!$this->_allowENotation) {
                throw new iris\UnexpectedCharacterException(
                    'E notation numbers are not allowed'
                );
            }

            $lexer->extract();
            $exponent = (int)$lexer->extractRegexRange('0-9\-\+');

            $powScale = 0;

            if($exponent < 0) {
                $scale -= $exponent;
                $powScale = -1 * $exponent;
            }

            if($scale < 0 || $exponent >= 0) {
                $scale = 0;
            }

            $number = bcmul($number, bcpow(10, $exponent, $powScale), $scale);
        }

        $floatSuffix = false;
        $decimalSuffix = false;
        $next = strtolower($lexer->char);

        if($next == 'f') {
            if(!$this->_allowSuffixes) {
                throw new iris\UnexpectedCharacterException(
                    'Float suffixes are not allowed',
                    $lexer->getLocation()
                );
            }

            $lexer->extract();
            $floatSuffix = true;
        } else if($next == 'd') {
            if(!$this->_allowSuffixes) {
                throw new iris\UnexpectedCharacterException(
                    'Decimal suffixes are not allowed',
                    $lexer->getLocation()
                );
            }

            $lexer->extract();
            $decimalSuffix = true;
        }

        if($fraction !== null && !$decimalSuffix) {
            $floatSuffix = true;
        }

        if($floatSuffix) {
            return $lexer->newToken('literal/float', $number);
        }

        if($decimalSuffix) {
            return $lexer->newToken('literal/decimal', $number);
        }

        return $lexer->newToken('literal/integer', $whole);
    }

// Dump
    public function getDumpProperties() {
        return [
            'hex' => $this->_allowHex,
            'octal' => $this->_allowOctal,
            'e-notation' => $this->_allowENotation,
            'suffixes' => $this->_allowSuffixes
        ];
    }
}