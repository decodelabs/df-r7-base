<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap\rootDse;

use df;
use df\core;
use df\opal;

class Base implements opal\ldap\IRootDse, core\IDumpable {

    use core\collection\TArrayCollection;
    use core\collection\TArrayCollection_ScalarSortable;
    use core\collection\TArrayCollection_AssociativeValueMap;
    use core\collection\TArrayCollection_Seekable;
    use core\collection\TArrayCollection_MappedMovable;
    
    public static function factory(opal\ldap\IAdapter $adapter, array $data) {
        $class = 'df\\opal\\ldap\\rootDse\\'.$adapter->getConnection()->getType();

        if(!class_exists($class)) {
            $class = __CLASS__;
        }

        return new $class($adapter, $data);
    }

    public function __construct(opal\ldap\IAdapter $adapter, array $data) {
        $this->import($data);
    }

    public function getReductiveIterator() {
        return new core\collection\ReductiveMapIterator($this);
    }
    

    public function getNamingContexts() {
        $output = array();
        
        foreach($this['namingContexts'] as $dn) {
            $output[] = opal\ldap\Dn::factory($dn);
        }
        
        return $output;
    }
    
    public function getSubschemaSubentry() {
        return opal\ldap\Dn::factory($this['subschemaSubentry']);
    }
    
    public function supportsVersion($version) {
        return in_array($version, $this['supportedLDAPVersion']);
    }
    
    public function supportsSaslMechanism($mechanism) {
        return in_array(strtoupper($mechanism), $this['supportedSASLMechanisms']);
    }
    
    public function getSchemaDn() {
        return $this->getSubschemaSubentry();
    }


// Dump
    public function getDumpProperties() {
        return $this->_collection;
    }
}