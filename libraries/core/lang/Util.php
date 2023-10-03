<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\lang;

use DecodeLabs\Glitch;

class Util
{
    public static function isAnonymousObject($object)
    {
        if (!is_object($object)) {
            return false;
        }

        $class = get_class($object);
        return false !== strpos($class, 'class@anonymous');
    }

    public static function normalizeClassName(string $class): string
    {
        $name = [];
        $parts = explode(':', $class);

        while (!empty($parts)) {
            $part = trim((string)array_shift($parts));

            if (preg_match('/^class@anonymous(.+)(\(([0-9]+)\))/', $part, $matches)) {
                $name[] = Glitch::normalizePath($matches[1]) . ' : ' . ($matches[3] ?? null);
            } elseif (preg_match('/^eval\(\)\'d/', $part)) {
                $name = ['eval[ ' . implode(' : ', $name) . ' ]'];
            } else {
                $name[] = $part;
            }
        }

        return implode(' : ', $name);
    }



    public static function isFlagSet(?int $flags, ?int $flag): bool
    {
        if (!$flags || !$flag) {
            return false;
        }

        return ($flags & $flag) == $flag;
    }

    public function setFlag(?int $flags, ?int $flag): int
    {
        if (!$flags) {
            $flags = 0;
        }

        if ($flag) {
            $flags |= $flag;
        }

        return $flags;
    }

    public function removeFlag(?int $flags, ?int $flag): int
    {
        if (!$flags) {
            return 0;
        }

        if ($flag) {
            $flags &= ~$flag;
        }

        return $flags;
    }
}
