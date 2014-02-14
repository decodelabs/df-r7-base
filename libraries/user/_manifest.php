<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user;

use df;
use df\core;
use df\user;
use df\opal;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class AuthenticationException extends RuntimeException {}


// Constants
interface IState {
    const DEACTIVATED = -1;
    const GUEST = 0;
    const PENDING = 1;
    const BOUND = 2;
    const CONFIRMED = 3;

    const ALL = true;
    const NONE = false;
    const DEV = 666;
}



class RememberKey {
    public $userId;
    public $key;
}


// Interfaces
interface IManager extends core\IManager {
    // Client
    public function getClient();
    public function clearClient();
    public function canAccess($lock, $action=null, $linkTo=false);
    public function getAccessLock($lock);
    public function analyzePassword($password);

    // Session
    public function getSessionController();
    public function getSessionNamespace($namespace);

    // Options
    public function setClientOption($key, $value);
    public function getClientOption($key, $default=null);
    public function setClientOptions(array $options);
    public function getClientOptions();
    
    // Authentication
    public function isLoggedIn();
    public function loadAuthenticationAdapter($name);
    public function authenticate(user\authentication\IRequest $request);
    public function authenticateRememberKey(RememberKey $key);
    public function refreshClientData();
    public function importClientData(user\IClientDataObject $data);
    public function regenerateKeyring();
    public function instigateGlobalKeyringRegeneration();
    public function logout();
}

interface IUserModel {
    public function getClientData($id);
    public function getClientDataList(array $ids, array $emails=null);
    public function getAuthenticationDomainInfo(user\authentication\IRequest $request);
    public function generateKeyring(IClient $client);

    public function generateRememberKey(IClient $client);
    public function hasRememberKey(RememberKey $key);
    public function destroyRememberKey(RememberKey $key);
    public function purgeRememberKeys();

    public function getSessionBackend();

    public function fetchClientOptions($id);
    public function updateClientOptions($id, array $options);
}

interface IClientDataObject extends \ArrayAccess {
    public function getId();
    public function getEmail();
    public function getFullName();
    public function getNickName();
    public function getFirstName();
    public function getSurname();
    public function getStatus();
    public function getJoinDate();
    public function getLoginDate();
    public function getLanguage();
    public function getCountry();
    public function getTimezone();
    public function getGroupIds();
}

trait TNameExtractor {

    public function getFirstName() {
        $parts = explode(' ', $this->getFullName(), 2);
        return array_shift($parts);
    }

    public function getSurname() {
        $parts = explode(' ', $this->getFullName(), 2);
        return array_pop($parts);
    }
}

interface IActiveClientDataObject extends IClientDataObject {
    public function onAuthentication();
}

interface IClient extends IClientDataObject {
    public function setAuthenticationState($state);
    public function getAuthenticationState();
    public function isDeactivated();
    public function isGuest();
    public function isPending();
    public function isLoggedIn();
    public function isBound();
    public function isConfirmed();
    
    public function import(IClientDataObject $clientData);
    public function setKeyring(array $keyring);
    public function getKeyring();
    public function getKeyringTimestamp();

    public function hasOptions();
    public function hasOption($key);
    public function getOption($key, $default=null);
    public function getOptions();
    public function importOptions(array $options);
    
    public function canAccess(IAccessLock $lock, $action=null, $linkTo=false);
}


interface IAccessLock {
    public function getAccessLockDomain();
    public function lookupAccessKey(array $keys, $action=null);
    public function getDefaultAccess($action=null);
    public function getActionLock($action);
    public function getAccessLockId();
}

trait TAccessLock {

    public function getActionLock($action) {
        return new user\access\lock\Action($this, $action);
    }
}


interface IAccessControlled {
    public function shouldCheckAccess($flag=null);
    public function setAccessLocks(array $locks);
    public function addAccessLocks(array $locks);
    public function addAccessLock($lock);
    public function getAccessLocks();
    public function clearAccessLocks();
}


trait TAccessControlled {
    
    protected $_checkAccess = false;
    protected $_accessLocks = array();
    
    public function shouldCheckAccess($flag=null) {
        if($flag !== null) {
            $this->_checkAccess = (bool)$flag;
            return $this;
        }
        
        return $this->_checkAccess;
    }

    public function setAccessLocks(array $locks) {
        $this->_accessLocks = array();
        return $this->addAccessLocks($locks);
    }

    public function addAccessLocks(array $locks) {
        foreach($locks as $lock) {
            $this->addAccessLock($lock);
        }

        return $this;
    }
    
    public function addAccessLock($lock) {
        $this->_accessLocks[] = $lock;
        $this->_checkAccess = true;
        return $this;
    }
    
    public function getAccessLocks() {
        return $this->_accessLocks;
    }
    
    public function clearAccessLocks() {
        $this->_accessLocks = array();
        return $this;
    }
}



interface IPostalAddress extends core\IStringProvider, core\IArrayProvider {
    public function getPostOfficeBox();
    public function getStreetLine1();
    public function getStreetLine2();
    public function getStreetLine3();
    public function getMainStreetLine();
    public function getExtendedStreetLine();
    public function getFullStreetAddress();
    public function getLocality();
    public function getRegion();
    public function getPostalCode();
    public function getCountryCode();
    public function getCountryName();
    public function toOneLineString();
}

trait TPostalAddress {
    
    protected $_countryName;

    public function getMainStreetLine() {
        $output = $this->getStreetLine1();

        if($this->getStreetLine3() && $t = $this->getStreetLine2()) {
            $output .= ', '.$t;
        }

        return $output;
    }

    public function getExtendedStreetLine() {
        if($output = $this->getStreetLine3()) {
            return $output;
        }

        return $this->getStreetLine2();
    }

    public function getFullStreetAddress() {
        $output = $this->getStreetLine1();
        $address2 = $this->getStreetLine2();
        $address3 = $this->getStreetLine3();
        
        if(!empty($address2)) {
            $output .= ', '.$address2;
        }

        if(!empty($address3)) {
            $output .= ', '.$address3;
        }
        
        return $output;
    }

    public function getPostOfficeBox() {
        foreach([$this->getStreetLine1(), $this->getStreetLine2(), $this->getStreetLine3()] as $line) {
            if(substr(str_replace('.', ' ', strtolower($line)), 0, 6) == 'po box') {
                return $line;
            }
        }

        return null;
    }

    public function getCountryName() {
        if(!$this->_countryName) {
            $this->_countryName = core\i18n\Manager::getInstance()->countries->getName($this->getCountryCode());
        }

        return $this->_countryName;
    }

    public function toString() {
        $data = $this->toArray();
        $data['country'] = $this->getCountryName();
        $data['countryCode'] = null;

        return implode("\n", array_filter($data, function($line) {
            return !empty($line);
        }));
    }

    public function toOneLineString() {
        return $this->getFullStreetAddress().', '.$this->getLocality().', '.$this->getPostalCode().', '.$this->getCountryCode();
    }

    public function toArray() {
        return [
            'street1' => $this->getStreetLine1(),
            'street2' => $this->getStreetLine2(),
            'street3' => $this->getStreetLine3(),
            'locality' => $this->getLocality(),
            'region' => $this->getRegion(),
            'postalCode' => $this->getPostalCode(),
            'countryCode' => $this->getCountryCode()
        ];
    }
}