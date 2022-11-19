<?php

/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\access\lock;

use df\user;

class Boolean implements user\IAccessLock
{
    use user\TAccessLock;

    protected $_value = true;
    protected $_domain = 'dynamic';

    public function __construct($value)
    {
        $this->_value = (bool)$value;
    }

    public function setAccessLockDomain($domain)
    {
        $this->_domain = $domain;
        return $this;
    }

    public function getAccessLockDomain()
    {
        return $this->_domain;
    }

    public function lookupAccessKey(array $keys, $action = null)
    {
        return null;
    }

    public function getDefaultAccess($action = null)
    {
        return !$this->_value;
    }

    public function getAccessLockId()
    {
        return null;
    }
}
