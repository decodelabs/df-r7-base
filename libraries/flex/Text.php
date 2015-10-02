<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex;

use df;
use df\core;
use df\flex;

class Text implements IText, \IteratorAggregate, core\IDumpable {

    use core\TValueMap;
    use core\collection\TExtractList;
    use core\collection\TNaiveIndexedMovable;

    protected $_encoding = null;
    protected $_value;
    private $_pos = 0;


// Macros
    public static function formatName($name) {
        return self::factory($name)
            ->replace(['-', '_'], ' ')
            ->regexReplace('/([^ ])([A-Z])/u', '$1 $2')
            ->wordsToUpper()
            ->toString();
    }

    public static function formatInitials($name) {
        return self::factory($name)
            ->replace(['-', '_'], ' ')
            ->regexReplace('/([^ ])([A-Z])/u', '$1 $2')
            ->wordsToUpper()
            ->regexReplace('/[^A-Z0-9]/', '')
            ->toString();
    }

    public static function formatLabel($label) {
        return self::factory($label)
            ->replace(['-', '_'], ' ')
            ->regexReplace('/([a-z])([A-Z])/u', '$1 $2')
            ->toLower()
            ->firstToUpper()
            ->toString();
    }

    public static function formatId($id) {
        return self::factory($id)
            ->translitToAscii()
            ->regexReplace('/([^ ])([A-Z])/u', '$1 $2')
            ->replace(['-', '.', '+'], ' ')
            ->regexReplace('/[^a-zA-Z0-9_ ]/', '')
            ->wordsToUpper()
            ->replace(' ', '')
            ->toString();
    }

    public static function formatActionSlug($action) {
        return self::factory($action)
            ->translitToAscii()
            ->regexReplace('/([^ ])([A-Z])/u', '$1-$2')
            ->toLower()
            ->toString();
    }

    public static function formatSlug($slug, $allowedChars=null) {
        return self::factory($slug)
            ->translitToAscii()
            ->regexReplace('/([a-z][a-z])([A-Z][a-z])/u', '$1 $2')
            ->toLower()
            ->replace([' ', '.', ','], '-')
            ->replace('/', '_')
            ->regexReplace('/[^a-z0-9_\-'.preg_quote($allowedChars, '/').']/', '')
            ->regexReplace('/-+/', '-')
            ->toString();
    }

    public static function formatPathSlug($slug, $allowedChars=null) {
        $parts = explode('/', $slug);

        foreach($parts as $i => $part) {
            $part = trim($part);

            if(empty($part)) {
                unset($parts[$i]);
                continue;
            }

            $parts[$i] = self::formatSlug($part, $allowedChars);
        }

        if(empty($parts)) {
            return '/';
        }

        return implode('/', $parts);
    }

    public static function formatFileName($fileName, $allowSpaces=false) {
        $output = self::factory($fileName)
            ->translitToAscii()
            ->replace('/', '_')
            ->regexReplace('/[\/\\?%*:|"<>]/', '');

        if(!$allowSpaces) {
            $output->replace(' ', '-');
        }

        return $output->toString();
    }


    public static function shorten($string, $length, $right=false) {
        $length = (int)$length;

        if($length < 6) {
            $length = 6;
        }


        $output = self::factory($string);

        if($output->getLength() > ($length - 3)) {
            if($right) {
                $output->substring(-($length - 3))->prepend('...');
            } else {
                $output->substring(0, $length - 3)->append('...');
            }
        }

        return $output->toString();
    }


    public static function compare($string1, $string2) {
        $string1 = self::factory($string1);
        $string2 = self::factory($string2);

        $string1->replace("\r\n", "\n");
        $string2->replace("\r\n", "\n");

        $string2->convertEncoding($string1->getEncoding());

        return $string1->_value === $string2->_value;
    }


// Case flags
    public static function normalizeCaseFlag($case) {
        if(is_string($case)) {
            switch(strtolower(self::formatId($case))) {
                case 'upperwords':
                    $case = flex\ICase::UPPER_WORDS;
                    break;

                case 'upperfirst':
                    $case = flex\ICase::UPPER_FIRST;
                    break;

                case 'upper':
                    $case = flex\ICase::UPPER;
                    break;

                case 'lower':
                    $case = flex\ICase::LOWER;
                    break;

                case 'lowerfirst':
                    $case = flex\ICase::LOWER_FIRST;
                    break;

                default:
                    $case = flex\ICase::NONE;
                    break;
            }
        }

        switch($case) {
            case flex\ICase::UPPER_WORDS:
            case flex\ICase::UPPER_FIRST:
            case flex\ICase::UPPER:
            case flex\ICase::LOWER:
            case flex\ICase::LOWER_FIRST:
                break;

            default:
                $case = flex\ICase::NONE;
                break;
        }

        return $case;
    }

    public static function applyCase($string, $case, $encoding=flex\IEncoding::UTF_8) {
        return self::factory($string, $encoding)
            ->setCase($case)
            ->toString();
    }


// Alnum convert
    public static function numericToAlpha($number) {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        $number = (int)$number;
        $output = '';

        while($number >= 0) {
            $key = $number % 26;
            $output = $alphabet{$key}.$output;
            $number = (($number - $key) / 26) - 1;
        }

        return $output;
    }

    public static function alphaToNumeric($alpha) {
        $output = -1;

        for($i = 0; $i < $length = strlen($alpha); $i++) {
            $output = (($output + 1) * 26) + (base_convert($alpha{$i}, 36, 10) - 10);
        }

        return $output;
    }


// Checks
    public static function isAlpha($string) {
        return (bool)preg_match('/^[a-zA-Z]+$/', $string);
    }

    public static function isAlphaNumeric($string) {
        return (bool)preg_match('/^[a-zA-Z0-9]+$/', $string);
    }

    public static function isDigit($string) {
        return (bool)preg_match('/^[0-9]+$/', $string);
    }

    public static function isWhitespace($string) {
        return (bool)preg_match('/^\s+$/', $string);
    }


// Convert
    public static function stringToBoolean($value, $default=false) {
        if(is_bool($value)) {
            return $value;
        } else if($value === '') {
            return $default;
        } else if($value === null) {
            return false;
        }

        switch(strtolower($value)) {
            case 'false':
            case '0':
            case 'no':
            case 'n':
            case 'off':
            case 'disabled':
                return false;

            case 'true':
            case '1':
            case 'yes':
            case 'y':
            case 'on':
            case 'enabled':
                return true;

            default:
                return $default;
        }
    }


    public static function baseConvert($input, $fromBase, $toBase, $pad=1) {
        if($fromBase < 2
        || $fromBase > 36
        || $toBase < 2
        || $toBase > 36) {
            return false;
        }

        if(!is_string($input)) {
            $input = sprintf('%0.0F', $input);
        }


        if(extension_loaded('gmp')) {
            $output = gmp_strval(gmp_init($input, $fromBase), $toBase);

            if($pad > 1) {
                $output = str_pad($output, $pad, '0', STR_PAD_LEFT);
            }

            return $output;
        }


        $digitChars = '0123456789abcdefghijklmnopqrstuvwxyz';
        $inDigits = [];
        $outChars = '';

        $input = strtolower($input);
        $length = strlen($input);


        for($i = 0; $i < $length; $i++) {
            $digit = ord($input{$i}) - 48;

            if($digit > 9) {
                $digit -= 39;
            }

            if($digit > $fromBase) {
                return false;
            }

            $inDigits[] = $digit;
        }


        while(!empty($inDigits)) {
            $work = 0;
            $workDigits = [];

            foreach($inDigits as $digit) {
                $work *= $fromBase;
                $work += $digit;


                if($work < $toBase) {
                    if(!empty($workDigits)) {
                        $workDigits[] = 0;
                    }
                } else {
                    $workDigits[] = (int)($work / $toBase);
                    $work = $work % $toBase;
                }
            }

            $outChars = $digitChars{$work}.$outChars;
            $inDigits = $workDigits;
        }

        return str_pad($outChars, $pad, '0', STR_PAD_LEFT);
    }


    public static function ascii32ToHex32($string) {
        for($i = 0; $i < strlen($string); $i++) {
            $char = substr($string, $i, 1);

            if(ord($char) < 32) {
                $hex = dechex(ord($char));

                if(strlen($hex) == 1) {
                    $hex = '0'.$hex;
                }

                $string = str_replace($char, '\\'.$hex, $string);
            }
        }

        return $string;
    }

    public static function hex32ToAscii32($string) {
        return preg_replace_callback(
            "/\\\([0-9A-Fa-f]{2})/",
            function($matches) {
                return chr(hexdec($matches[0]));
            },
            $string
        );
    }

    public static function mbOrd($chr) {
        $h = ord($chr{0});

        if($h <= 0x7F) {
            return $h;
        } else if($h < 0xC2) {
            return false;
        } else if($h <= 0xDF) {
            return ($h & 0x1F) << 6 | (ord($chr{1}) & 0x3F);
        } else if($h <= 0xEF) {
            return ($h & 0x0F) << 12 | (ord($chr{1}) & 0x3F) << 6
                                     | (ord($chr{2}) & 0x3F);
        } else if($h <= 0xF4) {
            return ($h & 0x0F) << 18 | (ord($chr{1}) & 0x3F) << 12
                                     | (ord($chr{2}) & 0x3F) << 6
                                     | (ord($chr{3}) & 0x3F);
        } else {
            return false;
        }
    }


// Meta
    public static function countWords($value) {
        return self::factory(trim($value).' ')
            ->regexReplace('/[^\w\s]+/', '')
            ->regexReplace('/[\s]+/', ' ')
            ->substringCount(' ');
    }

    public static function splitWords($value, $strip=true, $expand=true) {
        $output = self::factory(trim($value));

        if($strip) {
            $output->regexReplace('/[^\w\s]+/', '');
        }

        if($expand) {
            $output->regexReplace('/([a-z])([A-Z])/u', '$1 $2');
        }

        $output = $output->regexReplace('/[\s]+/', ' ')->toString();
        return explode(' ', $output);
    }



// Construct
    public static function factory($value, $encoding=flex\IEncoding::UTF_8) {
        if($value instanceof IText) {
            return $value;
        }

        return new self($value, $encoding);
    }

    public function __construct($value, $encoding=flex\IEncoding::UTF_8) {
        $this->import($value);

        if($encoding === null) {
            $encoding = flex\IEncoding::UTF_8;
        }

        if($this->_encoding === null) {
            if($encoding != flex\IEncoding::UTF_8 // quick shortcut to avoid lots of lookups
            || $encoding == flex\IEncoding::AUTO
            || !self::isValidEncoding($encoding)) {
                // Add an ascii character to avoid the trailing char bug
                $encoding = mb_detect_encoding($value.'a');
            }
        }

        $this->_encoding = $encoding;
    }


// Encoding
    public static function isValidEncoding($encoding) {
        return in_array($encoding, mb_list_encodings());
    }

    public function hasValidEncoding() {
        return mb_check_encoding($this->_value, $this->_encoding);
    }

    public function getEncoding() {
        return $this->_encoding;
    }

    public function convertEncoding($encoding) {
        if($encoding == $this->_encoding) {
            return $this;
        }

        if(!self::isValidEncoding($encoding)) {
            throw new InvalidArgumentException($encoding.' is not a valid string encoding');
        }

        $this->_value = mb_convert_encoding($this->_value, $encoding, $this->_encoding);
        return $this;
    }

    public function toUtf8() {
        return $this->convertEncoding(flex\IEncoding::UTF_8);
    }

    public function translitToAscii() {
        $this->_value = str_replace(['À','Á','Â','Ã','Ä','Å'], 'A', $this->_value);
        $this->_value = str_replace(['È','É','Ê','Ë'], 'E', $this->_value);
        $this->_value = str_replace(['Ì','Í','Î','Ï'], 'I', $this->_value);
        $this->_value = str_replace(['Ò','Ó','Ô','Õ','Ö','Ø'], 'O', $this->_value);
        $this->_value = str_replace(['Ù','Ú','Û','Ü'], 'U', $this->_value);
        $this->_value = str_replace(['¥','Ý'], 'Y', $this->_value);

        $this->_value = str_replace(['à','á','â','ã','ä','å'], 'a', $this->_value);
        $this->_value = str_replace(['è','é','ê','ë'], 'e', $this->_value);
        $this->_value = str_replace(['ì','í','î','ï'], 'i', $this->_value);
        $this->_value = str_replace(['ð','ò','ó','ô','õ','ö','ø'], 'o', $this->_value);
        $this->_value = str_replace(['µ','ù','ú','û','ü'], 'u', $this->_value);
        $this->_value = str_replace(['ý','ÿ'], 'y', $this->_value);

        $this->_value = str_replace(
            ['Æ', 'æ', 'ß', 'Ç','ç','Ð','Ñ','ñ'],
            ['AE','ae','ss','C','c','D','N','n'],
            $this->_value
        );

        $this->_value = iconv($this->_encoding, 'ASCII//TRANSLIT', $this->_value);

        return $this;
    }


// Output
    public function __toString() {
        return $this->_value;
    }

    public function toString() {
        return $this->_value;
    }



// Indexes
    public function getIndex($value) {
        return $this->indexOf($value);
    }

    public function indexOf($needle, $offset=0) {
        return mb_strpos($this->_value, $needle, $offset, $this->_encoding);
    }

    public function iIndexOf($needle, $offset=0) {
        return mb_stripos($this->_value, $needle, $offset, $this->_endoding);
    }

    public function lastIndexOf($needle, $offset=0) {
        return mb_strrpos($this->_value, $needle, $offset, $this->_encoding);
    }

    public function iLastIndexOf($needle, $offset=0) {
        return mb_strripos($this->_value, $needle, $offset, $this->_encoding);
    }


    public function toIndexOf($needle, $offset=0) {
        if($offset <= 0) {
            if(false === ($output = mb_strstr($this->_value, $needle, true, $this->_encoding))) {
                return null;
            }

            return new self($output, $this->_encoding);
        }

        if(false === ($pos = $this->indexOf($needle, $offset))) {
            return null;
        }

        return new self(
            mb_substr($this->_value, 0, $pos, $this->_encoding),
            $this->_encoding
        );
    }

    public function fromIndexOf($needle, $offset=0) {
        if($offset <= 0) {
            $output = mb_strstr($this->_value, $needle, false, $this->_encoding);
        } else {
            $output = mb_strstr(mb_substr($this->_value, $offset), $needle, false, $this->_encoding);
        }

        if($output === false) {
            $output = null;
        }

        return new self($output, $this->_encoding);
    }

    public function iToIndexOf($needle, $offset=0) {
        if($offset <= 0) {
            if(false === ($output = mb_stristr($this->_value, $needle, true, $this->_encoding))) {
                return null;
            }

            return new self($output, $this->_encoding);
        }

        if(false === ($pos = $this->indexOf($needle, $offset))) {
            return null;
        }

        return new self(
            mb_substr($this->_value, 0, $pos, $this->_encoding),
            $this->_encoding
        );
    }

    public function iFromIndexOf($needle, $offset=0) {
        if($offset <= 0) {
            $output = mb_stristr($this->_value, $needle, false, $this->_encoding);
        } else {
            $output = mb_stristr(mb_substr($this->_value, $offset), $needle, false, $this->_encoding);
        }

        if($output === false) {
            $output = null;
        }

        return new self($output, $this->_encoding);
    }


    public function count() {
        return mb_strlen($this->_value, $this->_encoding);
    }


// Length
    public function getLength() {
        return mb_strlen($this->_value, $this->_encoding);
    }

    public function prepend($string) {
        $this->_value = $string.$this->_value;
        return $this;
    }

    public function append($string) {
        $this->_value .= $string;
        return $this;
    }



// Case
    public function setCase($case) {
        $case = self::normalizeCaseFlag($case);

        switch($case) {
            case flex\ICase::UPPER_WORDS:
                return $this->wordsToUpper();

            case flex\ICase::UPPER_FIRST:
                return $this->firstToUpper();

            case flex\ICase::UPPER:
                return $this->toUpper();

            case flex\ICase::LOWER:
                return $this->toLower();

            case flex\ICase::LOWER_FIRST:
                return $this->firstToLower();

            default:
                return $this;
        }
    }

    public function toUpper() {
        $this->_value = mb_strtoupper($this->_value, $this->_encoding);
        return $this;
    }

    public function firstToUpper() {
        $this->_value = mb_strtoupper(mb_substr($this->_value, 0, 1, $this->_encoding)).
            mb_substr($this->_value, 1, mb_strlen($this->_value, $this->_encoding), $this->_encoding);
        return $this;
    }

    public function wordsToUpper() {
        $this->_value = mb_convert_case($this->_value, MB_CASE_TITLE, $this->_encoding);
        return $this;
    }

    public function toLower() {
        $this->_value = mb_strtolower($this->_value, $this->_encoding);
        return $this;
    }

    public function firstToLower() {
        $this->_value = mb_strtolower(mb_substr($this->_value, 0, 1, $this->_encoding)).
            mb_substr($this->_value, 1, mb_strlen($this->_value, $this->_encoding), $this->_encoding);
        return $this;
    }



// Splitting
    public function substring($start, $length=null) {
        if($length === null) {
            $length = mb_strlen($this->_value, $this->_encoding);
        }

        $this->_value = mb_substr($this->_value, $start, $length, $this->_encoding);
        return $this;
    }

    public function getSubstring($start, $length=null) {
        if($length === null) {
            $length = mb_strlen($this->_value, $this->_encoding);
        }

        return mb_substr($this->_value, $start, $length, $this->_encoding);
    }

    public function substringCount($needle) {
        return mb_substr_count($this->_value, $needle, $this->_encoding);
    }


    public function truncate($length, $marker=null) {
        return $this->truncateFrom(0, $length, $marker);
    }

    public function truncateFrom($start, $length, $marker=null) {
        $this->_value = mb_strimwidth($this->_value, $start, $length, $marker, $this->_encoding);
        return $this;
    }



// Replace
    public function replace($in, $out) {
        $this->_value = str_replace($in, $out, $this->_value);
        return $this;
    }

    public function regexReplace($in, $out) {
        $this->_value = preg_replace($in, $out, $this->_value);
        return $this;
    }



// ICollection
    public function import($input) {
        if(!is_string($input)) {
            if($input instanceof self) {
                $this->_value = $input->_value;
                $this->_encoding = $input->_encoding;
                return $this;
            }

            if($input instanceof core\IArrayProvider) {
                $input = $input->toArray();
            }

            if(!is_array($input)) {
                return $this;
            }

            $input = implode('', $input);
        }


        $this->_value = (string)$input;
        $this->_encoding = mb_detect_encoding($input.'a');

        return $this;
    }

    public function toArray() {
        return preg_split('/(?<!^)(?!$)/u', $this->_value);
    }

    public function isEmpty() {
        return mb_strlen($this->_value, $this->_encoding) == 0;
    }

    public function clear() {
        $this->_value = '';
        return $this;
    }


    public function set($index, $value) {
        $index = (int)$index;
        $length = mb_strlen($this->_value);

        if($index < 0) {
            $index += $length;

            if($index < 0) {
                throw new OutOfBoundsException(
                    'Trying to set a negative index outside of current bounds'
                );

                //return $this;
            }
        }

        if($index >= $length) {
            $this->_value .= $value;
            return $this;
        }

        $oldVal = $this->_value;
        $this->_value = '';

        if($index != 0) {
            $this->_value .= mb_substr($oldVal, 0, $index);
        }

        $this->_value .= $value;
        $indexLength = $index + max(mb_strlen($value), 1);

        if($indexLength > 0) {
            $this->_value .= mb_substr($oldVal, $indexLength);
        }

        return $this;
    }

    public function put($index, $value) {
        $index = (int)$index;
        $length = mb_strlen($this->_value);

        if($index < 0) {
            $index += $length;

            if($index < 0) {
                throw new OutOfBoundsException(
                    'Trying to set a negative index outside of current bounds'
                );

                //return $this;
            }
        }

        if($index >= $length) {
            $this->_value .= $value;
            return $this;
        }

        $this->_value = mb_substr($this->_value, 0, $index).$value.mb_substr($this->_value, $index);
        return $this;
    }

    public function get($index, $default=null) {
        $length = mb_strlen($this->_value);

        if($index >= $length) {
            return $default;
        }

        return mb_substr($this->_value, $index, 1);
    }

    public function has($index) {
        $length = mb_strlen($this->_value);

        return $index < $length && $index >= -$length;
    }

    public function remove($index) {
        return $this->set($index, '');
    }



    public function getCurrent() {
        return mb_substr($this->_value, $this->_pos, 1, $this->_encoding);
    }

    public function getNext() {
        return $this->seekNext()->getCurrent();
    }

    public function getPrev() {
        return $this->seekPrev()->getCurrent();
    }

    public function getFirst() {
        return $this->seekFirst()->getCurrent();
    }

    public function getLast() {
        return $this->seekLast()->getCurrent();
    }


    public function seekFirst() {
        $this->_pos = 0;
        return $this;
    }

    public function seekNext() {
        $this->_pos++;
        return $this;
    }

    public function seekPrev() {
        $this->_pos = max($this->_pos - 1, 0);
        return $this;
    }

    public function seekLast() {
        $this->_pos = mb_strlen($this->_value, $this->_encoding) - 1;
        return $this;
    }

    public function hasSeekEnded() {
        return $this->_pos >= mb_strlen($this->_value, $this->_encoding);
    }

    public function getSeekPosition() {
        return $this->_pos;
    }

    public function insert($value) {
        return $this->push($value);
    }

    public function extract() {
        return $this->shift();
    }

    public function pop() {
        $output = mb_substr($this->_value, -1, 1, $this->_encoding);
        $this->_value = mb_substr($this->_value, 0, -1, $this->_encoding);
        return $output;
    }

    public function push($value) {
        for($i = 0; $i < func_num_args(); $i++) {
            $this->_value .= func_get_arg($i);
        }

        return $this;
    }

    public function shift() {
        $output = mb_substr($this->_value, 0, 1, $this->_encoding);
        $this->_value = mb_substr($this->_value, 1, mb_strlen($this->_value, $this->_encoding), $this->_encoding);
        return $output;
    }

    public function unshift($value) {
        for($i = func_num_args() - 1; $i >= 0; $i--) {
            $this->_value = func_get_arg($i).$this->_value;
        }

        return $this;
    }


    public function slice($offset, $length=null) {
        $output = mb_substr($this->_value, $offset, $length, $this->_encoding);
        $this->_value = mb_substr($this->_value, 0, $offset, $this->_encoding).
            ($length !== null ? mb_substr($this->_value, $length) : null);

        return $output;
    }

    public function getSlice($offset, $length=null) {
        return mb_substr($this->_value, $offset, $length, $this->_encoding);
    }

    public function removeSlice($offset, $length=null) {
        $this->slice($offset, $length);
        return $this;
    }

    public function keepSlice($offset, $length=null) {
        $this->_value = $this->getSlice($offset, $length);
        return $this;
    }


    public function offsetSet($index, $value) {
        return $this->set($index, $value);
    }

    public function offsetGet($index) {
        return $this->get($index);
    }

    public function offsetExists($index) {
        return $this->has($index);
    }

    public function offsetUnset($index) {
        return $this->remove($index);
    }

    public function getIterator() {
        return new core\collection\SeekableIterator($this);
    }



// Dump
    public function getDumpProperties() {
        return $this->_value;
    }
}