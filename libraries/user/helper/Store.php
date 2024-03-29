<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\helper;

use df\user;

class Store extends Base implements user\ISessionBackedHelper
{
    use user\TSessionBackedHelper;

    public function set($key, $value)
    {
        $this->offsetSet($key, $value);
        return $this;
    }

    public function get($key, $default = null)
    {
        if (null === ($output = $this->offsetGet($key))) {
            $output = $default;
        }

        return $output;
    }

    public function has(...$keys)
    {
        foreach ($keys as $key) {
            if ($this->offsetExists($key)) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$keys)
    {
        foreach ($keys as $key) {
            $this->offsetUnset($key);
        }

        return $this;
    }
}
