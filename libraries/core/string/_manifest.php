<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\string;

use df;
use df\core;

// Exceptions
interface IException {}
class OutOfBoundsException extends \OutOfBoundsException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}



// Constants
interface IEncoding {
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
    const A7BIT = '7bit';
    const A8BIT = '8bit';
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

    const QP = 'quoted-printable';
    const BINARY = 'binary';
    const AUTO = 'auto';
}

interface IEncodingAware {
    public function getEncoding();
}

interface IEncodingProvider extends IEncodingAware {
    public function setEncoding($encoding);
}

trait TEncodingProvider {

    protected $_encoding = 'utf-8';

    public function setEncoding($encoding) {
        if(!core\string\Manipulator::isValidEncoding($encoding)) {
            throw new InvalidArgumentException(
                $encoding.' is not a valid encoding'
            );
        }

        $this->_encoding = $encoding;
        return $this;
    }

    public function getEncoding() {
        return $this->_encoding;
    }
}


interface ICase {
    const UPPER_WORDS = 3;
    const UPPER_FIRST = 2;
    const UPPER = 1;
    const NONE = 0;
    const LOWER = -1;
    const LOWER_FIRST = -2;
}


// Interfaces
interface IStringEscapeHandler {
    public function esc($value);
}

trait THtmlStringEscapeHandler {
    
    public function esc($value) {
        try {
            return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
        } catch(\Exception $e) {
            return $value;
        }
    }
}



interface ICharacterSetAware {
    public function setCharacterSet($charset);
    public function getCharacterSet();
}

interface ICollationAware {
    public function setCollation($collation);
    public function getCollation();
}



interface IGenerator {
    public static function random($minLength=6, $maxLength=14, $additionalChars=null);
    public static function passKey();
    public static function sessionId($salt=null);
    
    public static function randomBytes($bytes);
    
    public static function uuid1($node=null, $time=null);
    public static function uuid3($name, $namespace=null);
    public static function uuid4();
    public static function uuid5($name, $namespace=null);
    public static function combGuid();
}

interface IUtil {
    public static function parseDelimited($input, $delimiter=',', $quoteMap='"\'', $terminator=null);
    public static function implodeDelimited(array $data, $delimiter=',', $quote='\'', $terminator=null);
    
    public static function getCallableId(Callable $callable);

    public static function isAlpha($string);
    public static function isAlphaNumeric($string);
    public static function isDigit($string);
    public static function isWhitespace($string);

    public static function mbOrd($chr);

    public static function likeMatch($pattern, $string, $char='_', $wildcard='%');
    public static function generateLikeMatchRegex($pattern, $char='_', $wildcard='%', $delimiter='/');
    public static function contains($pattern, $string);
    public static function begins($pattern, $string);
    public static function ends($pattern, $string);
    
    public static function passwordHash($password, $salt, $iterations=1000);
    public static function encrypt($message, $password, $salt);
    public static function decrypt($message, $password, $salt);
}


interface IManipulator extends core\collection\IIndexedCollection, core\IStringProvider {
    
// Macros
    public static function formatName($name);
    public static function formatInitials($name);
    public static function formatLabel($label);
    public static function formatId($id);
    public static function formatSlug($slug, $allowedChars=null);
    public static function formatPathSlug($slug, $allowChars=null);
    public static function formatFileName($fileName, $allowSpaces=false);
    
    public static function compare($string1, $string2);

    public static function normalizeCaseFlag($case);
    public static function applyCase($string, $case);

    public static function numericToAlpha($number);
    public static function alphaToNumeric($alpha);
    
    public static function stringToBoolean($value);
    public static function baseConvert($input, $fromBase, $toBase, $pad=1);

    public static function countWords($value);
    public static function splitWords($value, $strip=true, $expand=true);
    
    
// Encoding
    public static function isValidEncoding($encoding);
    public function getEncoding();
    public function hasValidEncoding();
    public function convertEncoding($encoding);
    public function toUtf8();
    public function translitToAscii();
    
// Indexes
    public function indexOf($needle, $offset=0);
    public function iIndexOf($needle, $offset=0);
    public function lastIndexOf($needle, $offset=0);
    public function iLastIndexOf($needle, $offset=0);
    
    public function toIndexOf($needle, $offset=0);
    public function fromIndexOf($needle, $offset=0);
    public function iToIndexOf($needle, $offset=0);
    public function iFromIndexOf($needle, $offset=0);

// Lenth
    public function getLength();
    public function append($string);

// Case
    public function wordsToUpper();
    public function firstToUpper();
    public function toUpper();
    public function toLower();
    public function firstToLower();

// Splitting
    public function substring($start, $length=null);
    public function getSubstring($start, $length=null);
    public function substringCount($needle);
    
    public function truncate($length, $marker=null);
    public function truncateFrom($start, $length, $marker=null);
    
// Replace
    public function replace($in, $out);
    public function regexReplace($in, $out);
}


interface IPasswordAnalyzer {
    public function getHash();
    public function getPassKey();
    public function getLength();
    public function isCommon();
    public function getCharsetSize();
    public function getEntropy();
    public function getStrength();
}



interface IUuid extends core\IStringProvider {
    public static function v1($node=null, $time=null);
    public static function v3($name, $namespace=null);
    public static function v4();
    public static function v5($name, $namespace=null);
    
    public function getBytes();
    public function getHex();
    public function getUrn();
    public function getVersion();
    public function getVariant();
    public function getVariantName();
    public function getNode();
    public function getTime();
}