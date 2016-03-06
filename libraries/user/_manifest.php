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
use df\mesh;

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



// Interfaces
interface IManager extends core\IManager, mesh\event\IEmitter {
    // Client
    public function getClient();
    public function clearClient();
    public function isLoggedIn();
    public function refreshClientData();
    public function importClientData(user\IClientDataObject $data);
    public function regenerateKeyring();
    public function instigateGlobalKeyringRegeneration();

    public function isA(...$signifiers);
    public function canAccess($lock, $action=null, $linkTo=false);
    public function getAccessLock($lock);
    public function clearAccessLockCache();

    // Helpers
    public function getHelper($name);
}

interface IUserModel {
    public function getClientData($id);
    public function getClientDataList(array $ids, array $emails=null);
    public function getAuthenticationDomainInfo(user\authentication\IRequest $request);
    public function generateKeyring(IClient $client);

    public function fetchClientOptions($id);
    public function updateClientOptions($id, array $options);
    public function removeClientOptions($id, $keys);
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
    public function getSignifiers();
}

trait TNameExtractor {

    public function getFirstName() {
        $parts = preg_split('/\s+|\./', trim($this->getFullName()));
        static $titles = ['mr', 'mrs', 'miss', 'ms', 'mx', 'master', 'maid', 'madam', 'dr'];

        do {
            $output = array_shift($parts);
            $test = strtolower(str_replace([',', '.', '-'], '', $output));
        } while(count($parts) > 1 && in_array($test, $titles));

        return ucfirst($output);
    }

    public function getSurname() {
        $parts = explode(' ', $this->getFullName());
        return array_pop($parts);
    }
}

interface IActiveClientDataObject extends IClientDataObject {
    public function onAuthentication(IClient $client);
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
    public function isA(...$signifiers);

    public function import(IClientDataObject $clientData);
    public function setKeyring(array $keyring);
    public function getKeyring();
    public function getKeyringTimestamp();

    public function canAccess(IAccessLock $lock, $action=null, $linkTo=false);
}


## Helpers
interface IHelper {
    public function getManager();
    public function getHelperName();
}

interface ISessionBackedHelper extends IHelper, \ArrayAccess, core\IShutdownAware {
    public function storeSessionData();
}

trait TSessionBackedHelper {

    protected $_sessionData = null;
    protected $_sessionDataNew = false;

    public function offsetSet($key, $value) {
        $this->_ensureSessionData();
        $this->_sessionData[$key] = $value;
        return $this;
    }

    public function offsetGet($key) {
        $this->_ensureSessionData();

        if(isset($this->_sessionData[$key])) {
            return $this->_sessionData[$key];
        }
    }

    public function offsetExists($key) {
        $this->_ensureSessionData();
        return isset($this->_sessionData[$key]);
    }

    public function offsetUnset($key) {
        $this->_ensureSessionData();
        unset($this->_sessionData[$key]);
        return $this;
    }


    protected function _ensureSessionData() {
        if(isset($this->_sessionData)) {
            return;
        }

        $manager = $this->manager;
        $bucket = $manager->session->getBucket($manager::USER_SESSION_BUCKET);
        $this->_sessionData = $bucket->get($this->getHelperName());

        if($this->_sessionData === null) {
            $this->_sessionData = $this->_generateDefaultSessionData();
            $this->_sessionDataNew = true;
        }
    }

    protected function _generateDefaultSessionData() {
        return [];
    }

    protected function _destroySessionData() {
        $manager = $this->manager;
        $bucket = $manager->session->getBucket($manager::USER_SESSION_BUCKET);
        $bucket->remove($this->getHelperName());
        $this->_sessionData = null;
    }

    public function onApplicationShutdown() {
        $this->storeSessionData();
    }

    public function storeSessionData() {
        if($this->_sessionData === null || (empty($this->_sessionData) && $this->_sessionDataNew)) {
            return;
        }

        $manager = $this->manager;
        $bucket = $manager->session->getBucket($manager::USER_SESSION_BUCKET);

        if(empty($this->_sessionData)) {
            $bucket->remove($this->getHelperName());
        } else {
            $bucket->set($this->getHelperName(), $this->_sessionData);
        }
    }

    public function getDumpProperties() {
        $this->_ensureSessionData();
        return $this->_sessionData;
    }
}



## Access
interface IAccessLock {
    public function getAccessLockDomain();
    public function lookupAccessKey(array $keys, $action=null);
    public function getDefaultAccess($action=null);
    public function getActionLock($action);
    public function getAccessLockId();
}

trait TAccessLock {

    public function getActionLock($node) {
        return new user\access\lock\Action($this, $node);
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
    protected $_accessLocks = [];

    public function shouldCheckAccess($flag=null) {
        if($flag !== null) {
            $this->_checkAccess = (bool)$flag;
            return $this;
        }

        return (bool)$this->_checkAccess;
    }

    public function setAccessLocks(array $locks) {
        $this->_accessLocks = [];
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
        $this->_accessLocks = [];
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