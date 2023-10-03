<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex;

class Matcher implements IMatcher
{
    public static function isLike($pattern, $string, $char = '?', $wildcard = '*')
    {
        return (bool)preg_match('/' . self::generateLikeRegex($pattern, $char, $wildcard) . '/i', (string)$string);
    }

    public static function generateLikeRegex($pattern, $char = '?', $wildcard = '*', $delimiter = '/')
    {
        if (is_array($pattern)) {
            $output = [];

            foreach (array_unique($pattern) as $part) {
                $part = str_replace([$char, $wildcard], [0xFE, 0xFF], $part);
                $part = preg_quote($part, $delimiter);
                $output[] = str_replace([0xFE, 0xFF], ['.', '.*'], $part);
            }

            return '^' . implode('|', $output) . '$';
        } else {
            $regex = str_replace([$char, $wildcard], [0xFE, 0xFF], $pattern);
            $regex = preg_quote($regex, $delimiter);
            return '^' . str_replace([0xFE, 0xFF], ['.', '.*'], $regex) . '$';
        }
    }

    public static function contains($pattern, $string)
    {
        if (is_array($pattern)) {
            foreach ($pattern as $part) {
                if (self::contains($part, $string)) {
                    return true;
                }
            }

            return false;
        }

        return (bool)preg_match('/' . preg_quote((string)$pattern, '/') . '/i', $string);
    }

    public static function begins($pattern, $string)
    {
        if (is_array($pattern)) {
            foreach ($pattern as $part) {
                if (self::begins($part, $string)) {
                    return true;
                }
            }

            return false;
        }

        return (bool)preg_match('/^' . preg_quote((string)$pattern, '/') . '/i', $string);
    }

    public static function ends($pattern, $string)
    {
        if (is_array($pattern)) {
            foreach ($pattern as $part) {
                if (self::ends($part, $string)) {
                    return true;
                }
            }

            return false;
        }

        return (bool)preg_match('/' . preg_quote((string)$pattern, '/') . '$/i', $string);
    }
}
