<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;
    
class Util implements IUtil {

    public static function flattenArray($array) {
        if(!is_array($array)) {
            return [$array];
        }

        $output = array();

        foreach($array as $value) {
            if(is_array($value)) {
                $output = array_merge($output, self::flattenArray($value));
            } else {
                $output[] = $value;
            }
        }

        return array_unique($output);
    }

    public static function isIterable($collection) {
        return is_array($collection) || $collection instanceof Traversable;
    }

    public static function ensureIterable($collection) {
        if(is_array($collection) || $collection instanceof Traversable) {
            return $collection;
        }

        if($collection instanceof core\IArrayProvider) {
            return $collection->toArray();
        }

        if(empty($collection)) {
            return [];
        } else {
            return [$collection];
        }
    }

    public static function normalizeEnumValue($value, array $map, $defaultValue=null) {
        if(in_array($value, $map)) {
            return $value;
        }

        if(isset($map[$value])) {
            return $map[$value];
        }

        return $defaultValue;
    }
}