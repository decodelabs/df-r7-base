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



// Interfaces
interface IManager extends core\IManager {
    // Client
    public function getClient();
    public function canAccess($lock, $action=null);
    public function getAccessLock($lock);

    public function analyzePassword($password);
    
    
    // Authentication
    public function isLoggedIn();
    public function authenticate(user\authentication\IRequest $request);
    public function logout();
    
    
    // Session
    public function setSessionPerpetuator(ISessionPerpetuator $perpetuator);
    public function getSessionPerpetuator();
    public function setSessionBackend(ISessionBackend $backend);
    public function getSessionBackend();
    public function getSessionCache();
    public function getSessionDescriptor();
    public function getSessionId();
    public function transitionSessionId();
    public function isSessionOpen();
    
    public function getSessionNamespace($namespace);
    public function destroySession();
}

interface IUserModel {
    public function getAuthenticationDomainInfo(user\authentication\IRequest $request);
    public function generateKeyring(IClient $client);
}

interface IClientDataObject {
    public function getId();
    public function getEmail();
    public function getFullName();
    public function getNickName();
    public function getStatus();
    public function getJoinDate();
    public function getLoginDate();
    public function getLanguage();
    public function getCountry();
    public function getTimezone();
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
    
    public function canAccess(IAccessLock $lock, $action=null);
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



interface ISessionHandler extends core\IValueMap, \ArrayAccess {
    public function getSessionDescriptor();
    public function getSessionId();
    public function transitionSessionId();
    public function isSessionOpen();
    
    public function acquire($key);
    public function release($key);
    public function update($key, \Closure $func);
    public function refresh($key);
    public function refreshAll();
    public function getUpdateTime($id);
    public function getTimeSinceLastUpdate($key);
    
    public function getAllKeys();
    public function clear();
    public function prune($age=7200);
    
    public function __set($key, $value);
    public function __get($key);
    public function __isset($key);
    public function __unset($key);
    
    public function getLastUpdated();
}

interface ISessionBackend {
    public function setLifeTime($lifeTime);
    public function getLifeTime();
    
    public function insertDescriptor(ISessionDescriptor $descriptor);
    public function fetchDescriptor($id, $transitionTime);
    public function touchSession(ISessionDescriptor $descriptor);
    public function applyTransition(ISessionDescriptor $descriptor);
    public function killSession(ISessionDescriptor $descriptor);
    public function idExists($id);
    
    public function getNamespaceKeys(ISessionDescriptor $descriptor, $namespace);
    public function pruneNamespace(ISessionDescriptor $descriptor, $namespace, $age);
    public function clearNamespace(ISessionDescriptor $descriptor, $namespace);
    
    public function fetchNode(ISessionDescriptor $descriptor, $namespace, $key);
    public function fetchLastUpdatedNode(ISessionDescriptor $descriptor, $namespace);
    public function lockNode(ISessionDescriptor $descriptor, \stdClass $node);
    public function unlockNode(ISessionDescriptor $descriptor, \stdClass $node);
    public function updateNode(ISessionDescriptor $descriptor, \stdClass $node);
    public function removeNode(ISessionDescriptor $descriptor, $namespace, $key);
    public function hasNode(ISessionDescriptor $descriptor, $namespace, $key);
    public function collectGarbage();
}


interface ISessionDescriptor extends core\IArrayProvider, opal\query\IDataRowProvider {
    public function isNew();
    public function hasJustStarted($flag=null);
    
    public function setInternalId($id);
    public function getInternalId();
    public function setExternalId($id);
    public function getExternalId();
    
    public function setTransitionId($id);
    public function getTransitionId();
    public function applyTransition($newExternalId);
    
    public function setUserId($id);
    public function getUserId();
    
    public function setStartTime($time);
    public function getStartTime();
    
    public function setAccessTime($time);
    public function getAccessTime();
    public function isAccessOlderThan($seconds);
    
    public function setTransitionTime($time);
    public function getTransitionTime();
    public function hasJustTransitioned($transitionLifeTime=10);
    
    public function needsTouching($transitionLifeTime=10);
    public function touchInfo($transitionLifeTime=10);
}


interface ISessionPerpetuator {
    public function setLifeTime($lifeTime);
    public function getLifeTime();
    
    public function getInputId();
    public function perpetuate(user\IManager $manager, ISessionDescriptor $descriptor);
}




interface IAddress {
    public function getPostOfficeBox();
    public function getStreetAddress();
    public function getExtendedAddress();
    public function getFullStreetAddress();
    public function getLocality();
    public function getRegion();
    public function getPostalCode();
    public function getCountryCode();
}
