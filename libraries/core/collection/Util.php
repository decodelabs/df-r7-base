<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

use DecodeLabs\Tagged\Markup;

class Util implements IUtil
{
    public static function flatten($data, bool $unique=true, bool $removeNull=false)
    {
        if (!self::isIterable($data)) {
            return [$data];
        }

        $output = [];
        $sort = \SORT_STRING;

        foreach ($data as $key => $value) {
            if (is_object($value)) {
                $sort = \SORT_REGULAR;
            }


            if ($isIterable = self::isIterable($value)) {
                $outer = $value;
            } else {
                $outer = null;
            }

            if ($isContainer = $value instanceof core\IValueContainer) {
                $value = $value->getValue();
            }

            if ((!$isIterable || $isContainer)
            && (!$removeNull || $value !== null)) {
                if (is_string($key)) {
                    $output[$key] = $value;
                } else {
                    $output[] = $value;
                }
            }

            if ($isIterable) {
                $output = array_merge($output, self::flatten($outer, $unique, $removeNull));
            }
        }

        if ($unique) {
            return array_unique($output, $sort);
        } else {
            return $output;
        }
    }

    public static function leaves($data, bool $removeNull=false)
    {
        if (!self::isIterable($data)) {
            yield $data;
        }

        foreach ($data as $key => $value) {
            if ($isIterable = (
                self::isIterable($value) &&
                !$value instanceof Markup
            )) {
                $outer = $value;
            } else {
                $outer = null;
            }

            if ($isContainer = $value instanceof core\IValueContainer) {
                $value = $value->getValue();
            }

            if ((!$isIterable || $isContainer)
            && (!$removeNull || $value !== null)) {
                yield $key => $value;
            }

            if ($isIterable) {
                yield from self::leaves($outer, $removeNull);
            }
        }
    }

    public static function isArrayAssoc(array $array)
    {
        return !empty($array) && array_keys($array)[0] !== 0;
    }

    public static function isIterable($collection)
    {
        return is_array($collection) || $collection instanceof \Traversable;
    }

    public static function ensureIterable($collection)
    {
        if (self::isIterable($collection)) {
            return $collection;
        }

        if ($collection instanceof core\IArrayProvider) {
            return $collection->toArray();
        }

        if (empty($collection)) {
            return [];
        } else {
            return [$collection];
        }
    }

    public static function normalizeEnumValue($value, array $map, $defaultValue=null)
    {
        if (in_array($value, $map, true)) {
            return $value;
        }

        if (isset($map[$value])) {
            return $map[$value];
        }

        return $defaultValue;
    }

    public static function exportArray(array $values, $level=1)
    {
        $output = '['."\n";

        $i = 0;
        $count = count($values);
        $isNumericIndex = true;

        foreach ($values as $key => $val) {
            if ($key !== $i++) {
                $isNumericIndex = false;
                break;
            }
        }

        $i = 0;

        foreach ($values as $key => $val) {
            $output .= str_repeat('    ', $level);

            if (!$isNumericIndex) {
                $output .= '\''.addslashes($key).'\' => ';
            }

            if (is_object($val) || is_null($val)) {
                $output .= 'null';
            } elseif (is_array($val)) {
                $output .= self::exportArray($val, $level + 1);
            } elseif (is_int($val) || is_float($val)) {
                $output .= $val;
            } elseif (is_bool($val)) {
                if ($val) {
                    $output .= 'true';
                } else {
                    $output .= 'false';
                }
            } else {
                $output .= '\''.addslashes($val).'\'';
            }

            if (++$i < $count) {
                $output .= ',';
            }

            $output .= "\n";
        }

        $output .= str_repeat('    ', $level - 1).']';

        return $output;
    }
}
