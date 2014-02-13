<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\ldap;

use df;
use df\core;
use df\opal;

class Identity implements IIdentity {
    
    protected $_username;
    protected $_password;
    protected $_domain;
    protected $_domainType = null;

    public static function factory($username, $password=null, $domain=null, $domainType=null) {
        if($username instanceof IIdentity) {
            return $username;
        }

        return new self($username, $password, $domain);
    }

    public function __construct($username, $password=null, $domain=null, $domainType=null) {
        $this->setUsername($username);
        $this->setPassword($password);

        if($domain !== null) {
            $this->setDomain($domain, $domainType);
        }
    }

    public function setUsername($username) {
        if(false !== strpos($username, '@')) {
            $parts = explode('@', $username, 2);
            $username = array_shift($parts);
            $this->setDomain(array_shift($parts), 'upn');
        }
        
        if(false !== strpos($username, '\\')) {
            $parts = explode('\\', $username, 2);
            $this->setDomain(array_shift($parts), 'uid');
            $username = array_shift($parts);
        }

        if(false !== strpos($username, '=')) {
            $dn = Dn::factory($username);
            $username = $dn[0];
            $dn->shift();
            $this->setDomain($dn->buildDomain(), 'dn');
        }

        $this->_username = $username;
        return $this;
    }

    public function getUsername() {
        return $this->_username;
    }

    public function getPreparedUsername(IConnection $connection, IContext $context) {
        $connectionType = $connection->getType();
        $domainType = $this->_domainType;
        $domain = $this->_domain;

        if($connectionType == 'OpenLdap') {
            $domainType = 'dn';
        } else if($connectionType == 'ActiveDirectory') {
            if(!$domainType) {
                $domainType = 'upn';
            }
        }

        switch($domainType) {
            case 'upn':
                if($connectionType == 'ActiveDirectory') {
                    if(!$domain) {
                        $domain = $context->getUpnDomain();
                    }

                    return $this->_username.'@'.$domain;
                } else {
                    return $this->_username;
                }

            case 'uid':
                if($connectionType == 'ActiveDirectory') {
                    if(!$domain) {
                        $domain = $context->getDomain();
                    }

                    return $domain.'\\'.$this->_username;
                } else {
                    return $this->_username;
                }

            case 'dn':
                $username = $this->_username;

                if(!$username instanceof IRdn) {
                    $username = Rdn::factory('cn='.$username);
                }

                if(!$domain) {
                    $domain = clone $context->getBaseDn();
                    $domain->unshift('ou=users');
                }

                $output = Dn::factory($domain);
                $output->unshift($username);
                return $output;

            default:
                return $this->_username;
        }
    }
    
    public function setPassword($password) {
        $this->_password = $password;
        return $this;
    }
    
    public function getPassword() {
        return $this->_password;
    }

    public function setDomain($domain, $type=null) {
        $this->_domain = $domain;
        $this->_domainType = $type;
        return $this;
    }

    public function getDomain() {
        return $this->_domain;
    }

    public function getDomainType() {
        return $this->_domainType;
    }

    public function hasUidDomain() {
        return $this->_domainType == 'uid';
    }

    public function hasUpnDomain() {
        return $this->_domainType == 'upn';
    }

    public function hasDnDomain() {
        return $this->_domainType == 'dn';
    }
}