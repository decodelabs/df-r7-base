<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\string;

use df;
use df\core;

class Manipulator implements IManipulator, \IteratorAggregate, core\IDumpable {
    
    use core\collection\TExtractList;
    
    const UCS_4 = 'UCS-4';
    const UCS_4BE = 'UCS-4BE';
    const UCS_4LE = 'UCS-4LE';
    const UCS_2 = 'UCS-2';
    const UCS_2BE = 'UCS-2BE';
    const UCS_2LE = 'UCS-2LE';
    const UTF_32 = 'UTF-32';
    const UTF_32BE = 'UTF-32BE';
    const UTF_32LE = 'UTF-32LE';
    const UTF_16 = 'UTF-16';
    const UTF_16BE = 'UTF-16BE';
    const UTF_16LE = 'UTF-16LE';
    const UTF_7 = 'UTF-7';
    const UTF7_IMAP = 'UTF7-IMAP';
    const UTF_8 = 'UTF-8';
    const ASCII = 'ASCII';
    const EUC_JP = 'EUC-JP';
    const SJIS = 'SJIS';
    const EUC_JP_WIN = 'eucJP-win';
    const SJIS_WIN = 'SJIS-win';
    const ISO_2022_JP = 'ISO-2022-JP';
    const JIS = 'JIS';
    const ISO_8859_1 = 'ISO-8859-1';
    const ISO_8859_2 = 'ISO-8859-2';
    const ISO_8859_3 = 'ISO-8859-3';
    const ISO_8859_4 = 'ISO-8859-4';
    const ISO_8859_5 = 'ISO-8859-5';
    const ISO_8859_6 = 'ISO-8859-6';
    const ISO_8859_7 = 'ISO-8859-7';
    const ISO_8859_8 = 'ISO-8859-8';
    const ISO_8859_9 = 'ISO-8859-9';
    const ISO_8859_10 = 'ISO-8859-10';
    const ISO_8859_13 = 'ISO-8859-13';
    const ISO_8859_14 = 'ISO-8859-14';
    const ISO_8859_15 = 'ISO-8859-15';
    const BYTE2BE = 'byte2be';
    const BYTE2LE = 'byte2le';
    const BYTE4BE = 'byte4be';
    const BYTE4LE = 'byte4le';
    const BASE64 = 'BASE64';
    const HTML_ENTITIES = 'HTML-ENTITIES';
    const E_7BIT = '7bit';
    const E_8BIT = '8bit';
    const EUC_CN = 'EUC-CN';
    const CP936 = 'CP936';
    const HZ = 'HZ';
    const EUC_TW = 'EUC-TW';
    const CP950 = 'CP950';
    const BIG_5 = 'BIG-5';
    const EUC_KR = 'EUC-KR';
    const UHC = 'UHC';
    const ISO_2022_KR = 'ISO-2022-KR';
    const WINDOWS_1251 = 'Windows-1251';
    const WINDOWS_1252= 'Windows-1252';
    const CP866 = 'CP866';
    const KOI8_R = 'KOI8-R';
    const AUTO = 'auto';
    
    protected $_encoding = null;
    protected $_value;
    private $_pos = 0;
    
    
    
// Macros
    public static function formatName($name) {
        return self::factory($name)
            ->replace(array('-', '_'), ' ')
            ->regexReplace('/[^ ]([A-Z])/u', ' $1')
            ->wordsToUpper()
            ->toString();
    }
    
    public static function formatId($id) {
        return self::factory($id)
            ->translitToAscii()
            ->replace(array('-', '.', '+'), ' ')
            ->regexReplace('/[^a-zA-Z0-9_ ]/', '')
            ->wordsToUpper()
            ->replace(' ', '')
            ->toString();
    }
    
    public static function formatSlug($slug, $allowedChars=null) {
        return self::factory($slug)
            ->translitToAscii()
            ->toLower()
            ->replace(array(' ', '/'), array('-', '_'))
            ->regexReplace('/[^a-z0-9_\-'.preg_quote($allowedChars, '/').']/', '')
            ->toString();
    }
    
    public static function formatPathSlug($slug, $allowedChars=null) {
        $parts = explode('/', $slug);
        
        foreach($parts as &$part) {
            $part = self::formatSlug($part, $allowedChars);
        }
        
        return implode('/', $parts);
    }
    
    public static function formatFilename($filename, $allowSpaces=false) {
        $output = self::factory($filename)
            ->translitToAscii()
            ->replace('/', '_')
            ->regexReplace('/[\/\\?%*:|"<>]/', '');
            
        if(!$allowSpaces) {
            $output->replace(' ', '-');
        }
        
        return $output->toString();
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
    
    
// Convert
    public static function stringToBoolean($value) {
        switch(strtolower($value)) {
            case 'false':
            case '0':
            case 'no':
            case 'n':
            case 'off':
            case 'disabled':
                return false;
                
            default:
                return true;
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
        $inDigits = array();
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
            $workDigits = array();
            
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
    
    
    
// Construct
    public static function factory($value, $encoding=self::UTF_8) {
        if($value instanceof IManipulator) {
            return $value;
        }
        
        return new self($value, $encoding);
    }
    
    public function __construct($value, $encoding=self::UTF_8) {
        $this->import($value);
        
        if($this->_encoding === null) {
            if($encoding != self::UTF_8 // quick shortcut to avoid lots of lookups
            || $encoding == self::AUTO 
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
        return $this->convertEncoding(self::UTF_8);
    }
    
    public function translitToAscii() {
        $this->_value = str_replace(array('À','Á','Â','Ã','Ä','Å'), 'A', $this->_value);
        $this->_value = str_replace(array('È','É','Ê','Ë'), 'E', $this->_value);
        $this->_value = str_replace(array('Ì','Í','Î','Ï'), 'I', $this->_value);
        $this->_value = str_replace(array('Ò','Ó','Ô','Õ','Ö','Ø'), 'O', $this->_value);
        $this->_value = str_replace(array('Ù','Ú','Û','Ü'), 'U', $this->_value);
        $this->_value = str_replace(array('¥','Ý'), 'Y', $this->_value);
        
        $this->_value = str_replace(array('à','á','â','ã','ä','å'), 'a', $this->_value);
        $this->_value = str_replace(array('è','é','ê','ë'), 'e', $this->_value);
        $this->_value = str_replace(array('ì','í','î','ï'), 'i', $this->_value);
        $this->_value = str_replace(array('ð','ò','ó','ô','õ','ö','ø'), 'o', $this->_value);
        $this->_value = str_replace(array('µ','ù','ú','û','ü'), 'u', $this->_value);
        $this->_value = str_replace(array('ý','ÿ'), 'y', $this->_value);
        
        $this->_value = str_replace(
            array('Æ', 'æ', 'ß', 'Ç','ç','Ð','Ñ','ñ'),
            array('AE','ae','ss','C','c','D','N','n'),
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
    
    
    
// Case
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
        
        
        $this->_value = $input;
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
        
        if($indexLength < 0) {
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