<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\string;

use df\core;

abstract class Util implements IUtil {
    
    
    
// Delimited
    public static function parseDelimited($input, $delimiter=',', $quoteMap='"\'', $terminator=null) {
        $input = trim($input);
        
        if(!strlen($input)) {
            if($terminator !== null) {
                return array(array());
            } else {
                return array();
            }
        }
            
        if($terminator !== null) {
            $row = array();
            $input .= $terminator;
        } else {
            $input .= $delimiter;
        }
            
        $length = strlen($input);
        $mode = 0;
        $cell = '';
        $quote = null;
        $output = array();
        
        
        
        for($i = 0; $i < $length; $i++) {
            $char = $input{$i};
            
            switch($mode) {
                // post delimiter or start
                case 0:
                    if(ctype_space($char)) {
                        break;
                    } else if($char == $delimiter) {
                        if($terminator !== null) {
                            $row[] = $cell;
                        } else {
                            $output[] = $cell;
                        }
                        
                        $cell = '';
                    } else if(strstr($quoteMap, $char)) {
                        $quote = $char;
                        $mode = 2;
                    } else {
                        $cell .= $char;
                        $mode = 1;
                    }
                    
                    break;
                    
                // in cell
                case 1:
                    if($char == $delimiter) {
                        if($terminator !== null) {
                            $row[] = $cell;
                        } else {
                            $output[] = $cell;
                        }
                        
                        $cell = '';
                    } else {
                        $cell .= $char;
                    }
                    
                    break;
                    
                // in quote
                case 2:
                    if($char == '\\') {
                        $mode = 3;
                    } else if($char == $quote) {
                        $mode = 4;
                    } else {
                        $cell .= $char;
                    }
                    
                    break;
                    
                // escape in quote
                case 3:
                    $cell .= $char;
                    break;
                    
                // end of quote
                case 4:
                    $quote = null;
                    
                    if(ctype_space($char) && $char != $terminator) {
                        break;
                    }
                    
                    if($terminator !== null && $char == $terminator) {
                        $row[] = $cell;
                        $cell = '';
                        $output[] = $row;
                        $row = array();
                        $mode = 0;
                        break;
                    } else if($char == $delimiter) {
                        if($terminator !== null) {
                            $row[] = $cell;
                        } else {
                            $output[] = $cell;
                        }
                        
                        $cell = '';
                        $mode = 0;
                        break;
                    }
                    
                    throw new UnexpectedValueException(
                        'Unexpected character: '.$char.' at position '.$i.' in '.$input
                    );
            }
        }
        
        return $output;
    }

    public static function implodeDelimited(array $data, $delimiter=',', $quote='"', $terminator=null) {
        $output = array();
        
        if($terminator !== null) {
            foreach($data as $row) {
                foreach($row as $key => $value) {
                    $row[$key] = $quote.str_replace($quote, '\\'.$quote, $value).$quote;
                }
                
                $output[] = implode($delimiter, $row);
            }
            
            return implode($terminator, $output);
        } else {
            foreach($data as $value) {
                $output[] = $quote.str_replace($quote, '\\'.$quote, $value).$quote;
            }
            
            return implode($delimiter, $output);
        }
    }


// Callable
    public static function getCallableId(Callable $callable) {
        $output = '';

        if(is_array($callable)) {
            @list($target, $name) = $callable;

            if(is_object($target)) {
                $target = get_class($target);
            }

            $output = $target.'::'.$name;
        } else if($callable instanceof \Closure) {
            $output = 'closure-'.spl_object_hash($callable);
        } else if(is_object($callable)) {
            $output = get_class($callable);
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


// Ord
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

    
// Match
    public static function likeMatch($pattern, $string, $char='_', $wildcard='%') {
        return (bool)preg_match('/'.self::generateLikeMatchRegex($pattern, $char, $wildcard).'/i', $string);
    }
    
    public static function generateLikeMatchRegex($pattern, $char='_', $wildcard='%', $delimiter='/') {
        if(is_array($pattern)) {
            $output = array();
            
            foreach(array_unique($pattern) as $part) {
                $part = str_replace(array($char, $wildcard), array(0xFE, 0xFF), $part);
                $part = preg_quote($part, $delimiter);
                $output[] = str_replace(array(0xFE, 0xFF), array('.', '.*'), $part);
            }
            
            return '^'.implode('|', $output).'$';
        } else {
            $regex = str_replace(array($char, $wildcard), array(0xFE, 0xFF), $pattern);
            $regex = preg_quote($regex, '/');
            return '^'.str_replace(array(0xFE, 0xFF), array('.', '.*'), $regex).'$';
        }
    }
    
    public static function contains($pattern, $string) {
        if(is_array($pattern)) {
            foreach($pattern as $part) {
                if(self::contains($part, $string)) {
                    return true;
                }
            }
            
            return false;
        }
        
        return (bool)preg_match('/'.preg_quote($pattern, '/').'/i', $string);
    }
    
    public static function begins($pattern, $string) {
        if(is_array($pattern)) {
            foreach($pattern as $part) {
                if(self::contains($part, $string)) {
                    return true;
                }
            }
            
            return false;
        }
        
        return (bool)preg_match('/^'.preg_quote($compare, '/').'/i', $value);
    }
    
    public static function ends($pattern, $string) {
        if(is_array($pattern)) {
            foreach($pattern as $part) {
                if(self::contains($part, $string)) {
                    return true;
                }
            }
            
            return false;
        }
        
        return (bool)preg_match('/'.preg_quote($compare, '/').'$/i', $value);
    }
    
    
    
// Crypt
    const PBKDF2_ALGORITHM = 'sha256';
    const PBKDF2_KEY_LENGTH = 32;
    
    const ENCRYPTION_ALGORITHM = 'rijndael-256';
    const ENCRYPTION_MODE = 'ctr';
    const ENCRYPTION_IV_SIZE = 32;
    
    public static function passwordHash($password, $salt, $iterations=1000) {
        $hashLength = strlen(hash(self::PBKDF2_ALGORITHM, null, true));
        $keyBlocks = ceil(self::PBKDF2_KEY_LENGTH / $hashLength);
        $derivedKey = '';
        
        for($blockId = 1; $blockId <= $keyBlocks; $blockId++) {
            $initialBlock = $block = hash_hmac(self::PBKDF2_ALGORITHM, $salt.pack('N', $blockId), $password, true);
            
            for($i = 1; $i < $iterations; $i++) {
                $initialBlock ^= ($block = hash_hmac(self::PBKDF2_ALGORITHM, $block, $password, true));
            }
            
            $derivedKey .= $initialBlock;
        }
        
        return substr($derivedKey, 0, self::PBKDF2_KEY_LENGTH);
    }
    
    
    public static function encrypt($message, $password, $salt) {
        if(!$module = mcrypt_module_open(self::ENCRYPTION_ALGORITHM, '', self::ENCRYPTION_MODE, '')) {
            throw new RuntimeException(
                'Crypt module '.self::ENCRYPTION_ALGORITHM.' could not be loaded'
            );
        }
        
        $message = serialize($message);
        $iv = mcrypt_create_iv(self::ENCRYPTION_IV_SIZE, \MCRYPT_RAND);
        $key = self::passwordHash($password, $salt);
        
        if(mcrypt_generic_init($module, $key, $iv) !== 0) {
            throw new RuntimeException(
                'Unable to initialize crypt module'
            );
        }
        
        $message  = $iv.mcrypt_generic($module, $message);
        $message .= self::passwordHash($message, $key, 1000);
        
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        
        return $message;
    }
    
    public static function decrypt($message, $password, $salt) {
        if(!$module = mcrypt_module_open(self::ENCRYPTION_ALGORITHM, '', self::ENCRYPTION_MODE, '')) {
            throw new RuntimeException(
                'Crypt module '.self::ENCRYPTION_ALGORITHM.' could not be loaded'
            );
        }
        
        $iv = substr($message, 0, self::ENCRYPTION_IV_SIZE);
        $mac = substr($message, -self::PBKDF2_KEY_LENGTH);
        $message = substr($message, self::ENCRYPTION_IV_SIZE, -self::PBKDF2_KEY_LENGTH);
        $key = self::passwordHash($password, $salt);
        $compMac = self::passwordHash($iv.$message, $key, 1000);
        
        if($mac !== $compMac) {
            throw new InvalidArgumentException(
                'Unable to decrypt message, MAC entry does not match'
            );
        }
        
        if(mcrypt_generic_init($module, $key, $iv) !== 0) {
            throw new RuntimeException(
                'Unable to initialize crypt module'
            );
        }
        
        $message = mdecrypt_generic($module, $message);
        $message = unserialize($message);
        
        mcrypt_generic_deinit($module);
        mcrypt_module_close($module);
        
        return $message;
    }
}