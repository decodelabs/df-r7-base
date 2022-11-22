<?php

/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\access\lock;

use df\user;

class Virtual implements user\IAccessLock
{
    use user\TAccessLock;

    protected $_value;

    public function __construct($value)
    {
        $this->_value = $value;
    }

    public function getAccessLockDomain()
    {
        return 'virtual';
    }

    public function lookupAccessKey(array $keys, $action = null)
    {
        if (isset($keys[$this->_value])) {
            return $keys[$this->_value];
        }

        return null;
    }

    public function getDefaultAccess($action = null)
    {
        return false;
    }

    public function getAccessLockId()
    {
        return $this->_value;
    }
}
