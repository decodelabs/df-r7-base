<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\helper;

use df;
use df\core;
use df\user;

class Store extends Base implements user\ISessionBackedHelper {

    use user\TSessionBackedHelper;

    public function set($key, $value) {
        return $this->offsetSet($key, $value);
    }

    public function get($key, $default=null) {
        if(null === ($output = $this->offsetGet($key))) {
            $output = $default;
        }

        return $output;
    }

    public function has($key) {
        return $this->offsetExists($key);
    }

    public function remove($key) {
        return $this->offsetUnset($key);
    }
}