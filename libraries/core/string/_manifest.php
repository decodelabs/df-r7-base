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
    public static function formatId($id);
    public static function formatSlug($slug, $allowedChars=null);
    public static function formatPathSlug($slug, $allowChars=null);
    public static function formatFilename($filename, $allowSpaces=false);
    
    public static function numericToAlpha($number);
    public static function alphaToNumeric($alpha);
    
    public static function stringToBoolean($value);
    public static function baseConvert($input, $fromBase, $toBase, $pad=1);
    
    
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

// Splitting
    public function substring($start, $length=null);
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

interface IRainbowKey extends core\IStringProvider {
    public static function createFromHex($hexItemId, $generatorId, $itemIdSize=8);
    public static function create($itemId, $generatorId, $itemIdSize=8);
    
    public function getBytes();
    public function getHex();
    
    public function getGeneratorId();
    public function getItemId();
    public function getItemIdSize();
}