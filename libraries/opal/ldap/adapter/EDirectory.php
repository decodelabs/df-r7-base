<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap\adapter;

use df;
use df\core;
use df\opal;

class EDirectory extends opal\ldap\Adapter {
    
    const BIND_REQUIRES_DN = true;
    const UID_ATTRIBUTE = 'uid';
    
    protected function _prepareDn(opal\ldap\IDn $dn) {
        return $dn->implode(',', core\string\ICase::LOWER);
    }
}