<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\lang;

use df;
use df\core;

class Util {

    public static function isAnonymousObject($object) {
        if(!is_object($object)) {
            return false;
        }

        $class = get_class($object);
        return false !== strpos($class, 'class@anonymous');
    }

    public static function normalizeClassName(string $class): string {
        $name = [];
        $parts = explode(':', $class);

        while(!empty($parts)) {
            $part = trim(array_shift($parts));

            if(preg_match('/^class@anonymous(.+)(\(([0-9]+)\))/', $part, $matches)) {
                $name[] = core\fs\Dir::stripPathLocation($matches[1]).' : '.($matches[3] ?? null);
            } else if(preg_match('/^eval\(\)\'d/', $part)) {
                $name = ['eval[ '.implode(' : ', $name).' ]'];
            } else {
                $name[] = $part;
            }
        }

        return implode(' : ', $name);
    }
}