<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex;

use df;
use df\core;
use df\flex;
use df\opal;

class Json implements IJson {

// Encode
    public static function toString($data, int $flags=0): string {
        return json_encode(self::prepare($data), $flags);
    }

    public static function toFile($path, $data, int $flags=0): core\fs\IFile {
        return core\fs\File::create($path, self::toString($data, $flags));
    }



// Decode
    public static function fromString(/*?string*/ $data) {
        return json_decode($data, true);
    }

    public static function fromFile($path) {
        return self::fromString(core\fs\File::getContentsOf($path));
    }

    public static function stringToTree(/*?string*/ $data): core\collection\ITree {
        return core\collection\Tree::factory(self::fromString($data));
    }

    public static function fileToTree($path): core\collection\ITree {
        return core\collection\Tree::factory(self::fromFile($path));
    }



// Prepare
    public static function prepare($data) {
        if(is_scalar($data)) {
            return $data;
        }

        if($data instanceof opal\record\IPrimaryKeySet) {
            $data = $data->getValue();
        }

        if($data instanceof core\time\IDate) {
            if(!$data->hasTime()) {
                return $data->format(core\time\Date::DBDATE);
            } else {
                return $data->format(core\time\Date::W3C);
            }
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
            $data[$key] = self::prepare($value);
        }

        return $data;
    }
}