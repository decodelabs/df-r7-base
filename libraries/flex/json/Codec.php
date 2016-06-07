<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\json;

use df;
use df\core;
use df\flex;
use df\opal;

class Codec {

    public static function encode($data, int $flags=0) {
        return json_encode(self::prepareJsonData($data), $flags);
    }

    public static function encodeFile($path, $data, int $flags=0) {
        return core\fs\File::create($path, self::encode($data, $flags));
    }

    public static function prepareJsonData($data) {
        if(is_scalar($data)) {
            return $data;
        }

        if($data instanceof opal\record\IPrimaryKeySet) {
            $data = $data->getValue();
        }

        if($data instanceof core\time\IDate) {
            return $data->format(core\time\Date::W3C);
        } else if($data instanceof core\time\IDuration) {
            return $data->getSeconds();
        }

        if($data instanceof core\IArrayProvider) {
            $data = $data->toArray();
        }

        if(!is_array($data)) {
            if(method_exists($data, '__toString')) {
                return (string)$data;
            }

            return $data;
        }

        foreach($data as $key => $value) {
            $data[$key] = self::prepareJsonData($value);
        }

        return $data;
    }

    public static function decode($data) {
        return json_decode($data, true);
    }

    public static function decodeFile($path) {
        return self::decode(core\fs\File::getContentsOf($path));
    }

    public static function decodeAsTree($data) {
        return core\collection\Tree::factory(self::decode($data));
    }

    public static function decodeFileAsTree($path) {
        return core\collection\Tree::factory(self::decodeFile($path));
    }
}