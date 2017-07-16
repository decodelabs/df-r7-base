<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\crypt;

use df;
use df\core;


interface IUtil {
    public static function passwordHash($password, $salt, $iterations=1000);
}
