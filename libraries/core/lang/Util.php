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
}