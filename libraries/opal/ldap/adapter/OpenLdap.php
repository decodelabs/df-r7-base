<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap\adapter;

use df;
use df\core;
use df\opal;

class OpenLdap extends opal\ldap\Adapter {
    
    const BIND_REQUIRES_DN = true;
    const UID_ATTRIBUTE = 'uid';

    protected static $_metaFields = array(
        'objectClass', 'structuralObjectClass', 'entryUUID', 'creatorsName', 
        'createTimestamp', 'entryCSN', 'modifiersName', 'modifyTimestamp', 'entryDN', 
        'subschemaSubentry', 'hasSubordinates'
    );
    
    protected function _flattenDn(opal\ldap\IDn $dn) {
        return $dn->implode(',', core\string\ICase::LOWER);
    }
}