<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap;

use df;
use df\core;
use df\opal;

abstract class Adapter implements IAdapter {
    
    const BIND_REQUIRES_DN = true;
    const UID_ATTRIBUTE = 'uid';

    protected static $_arrayAttrs = array(
        'objectClass', 'memberOf', 'dSCorePropagationData', 'namingContexts',
        'supportedControl', 'supportedLDAPVersion', 'supportedLDAPPolicies',
        'supportedSASLMechanisms', 'supportedCapabilities'
    );
    
    protected static $_dateAttrs = array(
        'whenCreated', 'whenChanged', 'badPasswordTime', 'lastLogoff', 'lastLogon',
        'pwdLastSet', 'accountExpires', 'lastLogonTimestamp', 'currentTime'
    );
    
    protected static $_booleanAttrs = array(
        'isSynchronized', 'isGlobalCatalogReady'
    );
    
    protected static $_binaryAttrs = array(
        'objectGUID', 'objectSid'
    );
    
    protected $_connection;
    protected $_context;
    protected $_privilegedIdentity;
    protected $_boundIdentity;

    public static function factory($connection, $context, IIdentity $privilegedIdentity=null) {
        $connection = Connection::factory($connection);
        $context = Context::factory($context);

        $type = $connection->getType();

        if(!$type) {
            $type = 'Generic';
        }

        $class = 'df\\opal\\ldap\\adapter\\'.$type;

        if(!class_exists($class)) {
            throw new RuntimeException(
                'No adapter available for '.$type.' connection type'
            );
        }

        return new $class($connection, $context, $privilegedIdentity);
    }

    public static function getArrayAttributes() {
        return static::$_arrayAttrs;
    }
    
    public static function getDateAttributes() {
        return static::$_dateAttrs;
    }

    public static function getBooleanAttributes() {
        return static::$_booleanAttrs;
    }
    
    public static function getBinaryAttributes() {
        return static::$_binaryAttrs;
    }

    protected function __construct(IConnection $connection, IContext $context, IIdentity $privilegedIdentity=null) {
        $this->_connection = $connection;
        $this->setContext($context);
        $this->setPrivilegedIdentity($privilegedIdentity);
    }

// Connection
    public function getConnection() {
        return $this->_connection;
    }
    
    public function setContext($context) {
        $this->_context = Context::factory($context);
        return $this;
    }
    
    public function getContext() {
        return $this->_context;
    }
    
    public function getHash() {
        return $this->_connection->getHash();
    }
    

// Identity
    public function setPrivilegedIdentity(IIdentity $identity=null) {
        if($identity) {
            $this->normalizeIdentity($identity);
        }
        
        $this->_privilegedIdentity = $identity;
        return $this;
    }
    
    public function getPrivilegedIdentity() {
        return $this->_privilegedIdentity;
    }
    
    public function normalizeIdentity(IIdentity $identity, $autoFill=false) {
        if($identity->hasUid()) {
            if(!strlen($identity->getUidDomain())) {
                $identity->setUidDomain($this->_context->getDomain());
            }
            
            if($identity->getUidDomain() != $this->_context->getDomain()) {
                throw new DomainException(
                    'Identity is not part of domain '.$this->_context->getDomain()
                );
            }
        } else if($autoFill) {
            // fetch uid details
        }
        
        if($identity->hasUpn()) {
            
        } else if($autoFill) {
            // fetch upn details
        }
        
        return $this;
    }

    public function isBound() {
        return $this->_boundIdentity !== null;
    }
    
    public function getBoundIdentity() {
        return $this->_boundIdentity;
    }
    
    public function bind(IIdentity $identity) {
        $this->normalizeIdentity($identity);
        $this->_connection->bindIdentity($identity);
        $this->_boundIdentity = $identity;
        return $this;
    }
    
    public function ensureBind() {
        if(!$this->isBound()) {
            if($this->_privilegedIdentity) {
                $this->bind($this->_privilegedIdentity);
            } else {
                throw new BindException(
                    'No bind identity has been given'
                );
            }
        }
    }


// Helpers
    protected function _escapeValues(array $values) {
        foreach($values as $key => $value) {
            $values[$key] = $this->_escapeValue($value);
        }
        
        return $values;
    }
    
    protected function _escapeValue($value) {
        $value = str_replace(
            ['\\', '*', '(', ')'],
            ['\5c', '\2a', '\28', '\29'],
            $value
        );
        
        $value = core\string\Util::ascii32ToHex32($value);
        
        if($value === null) {
            $value = '\0';
        }
        
        return $value;
    }

    protected function _prepareDn(IDn $dn) {
        return (string)$dn;
    }
}