<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap\rootDse;

use df;
use df\core;
use df\opal;

class EDirectory extends Base {
    
    public function supportsExtension($oid) {
        return in_array($oid, $this['supportedExtension']);
    }
    
    public function getDsaName() {
        return opal\ldap\Dn::factory($this['dsaName']);
    }
}