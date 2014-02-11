<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap\rootDse;

use df;
use df\core;
use df\opal;

class ActiveDirectory extends Base {
    
    public function getConfiguationNamingContext() {
        return opal\ldap\Dn::factory($this['configurationNamingContext']);
    }
    
    public function getDefaultNamingContext() {
        return opal\ldap\Dn::factory($this['defaultNamingContext']);
    }
    
    public function getDsServiceName() {
        return opal\ldap\Dn::factory($this['dsServiceName']);
    }
    
    public function getRootDomainNamingContext() {
        return opal\ldap\Dn::factory($this['rootDomainNamingContext']);
    }
    
    public function getSchemaNamingContext() {
        return opal\ldap\Dn::factory($this['schemaNamingContext']);
    }
    
    public function getServerName() {
        return opal\ldap\Dn::factory($this['serverName']);
    }
    
    public function supportsCapability($oid) {
        return in_array($oid, $this['supportedCapabilities']);
    }
    
    public function supportsControl($oid) {
        return in_array($oid, $this['supportedControl']);
    }
    
    public function supportsPolicy($policy) {
        return in_array($policy, $this['supportedLDAPPolicies']);
    }
    
    public function getSchemaDn() {
        return $this->getSchemaNamingContext();
    }
}