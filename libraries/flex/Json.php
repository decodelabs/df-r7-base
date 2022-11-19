<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\File;

use df\core;
use df\opal;

use JsonSerializable;

class Json implements IJson
{
    // Encode
    public static function toString($data, int $flags = 0): string
    {
        return (string)json_encode(self::prepare($data), $flags);
    }

    public static function toFile($path, $data, int $flags = 0): File
    {
        return Atlas::createFile((string)$path, self::toString($data, $flags));
    }



    // Decode
    public static function fromString(?string $data)
    {
        return json_decode((string)$data, true);
    }

    public static function fromFile($path)
    {
        return self::fromString(Atlas::getContents((string)$path));
    }

    public static function stringToTree(?string $data): core\collection\ITree
    {
        return core\collection\Tree::factory(self::fromString($data));
    }

    public static function fileToTree($path): core\collection\ITree
    {
        return core\collection\Tree::factory(self::fromFile($path));
    }



    // Prepare
    public static function prepare($data)
    {
        if (is_scalar($data)) {
            return $data;
        }

        if ($data instanceof opal\record\IPrimaryKeySet) {
            $data = $data->getValue();
        }

        if ($data instanceof core\time\IDate) {
            if (!$data->hasTime()) {
                return $data->format(core\time\Date::DBDATE);
            } else {
                return $data->format(core\time\Date::W3C);
            }
        } elseif ($data instanceof core\time\IDuration) {
            return $data->getSeconds();
        }

        if ($data instanceof JSonSerializable) {
            $data = $data->jsonSerialize();
        } elseif ($data instanceof core\IArrayProvider) {
            $data = $data->toArray();
        }

        if (!is_array($data)) {
            if (
                is_object($data) &&
                method_exists($data, '__toString')
            ) {
                return (string)$data;
            }

            return $data;
        }

        foreach ($data as $key => $value) {
            $data[$key] = self::prepare($value);
        }

        return $data;
    }
}
