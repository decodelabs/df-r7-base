<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap\rootDse;

use df;
use df\core;
use df\opal;

class OpenLdap extends Base {
    
    public function getConfigContext() {
        return opal\ldap\Dn::factory($this['configContext']);
    }
    
    public function getMonitorContext() {
        return opal\ldap\Dn::factory($this['monitorContext']);
    }
    
    public function supportsControl($oid) {
        return in_array($oid, $this['supportedControl']);
    }
    
    public function supportsExtension($oid) {
        return in_array($oid, $this['supportedExtension']);
    }
    
    public function supportsFeature($oid) {
        return in_array($oid, $this['supportedFeatures']);
    }
}