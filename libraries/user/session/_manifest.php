<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\session;

use df;
use df\core;
use df\user;
use df\opal;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces    
interface IController {
    public function isOpen();
    public function setPerpetuator(IPerpetuator $perpetuator);
    public function getPerpetuator();
    public function setBackend(IBackend $backend);
    public function getBackend();
    public function getCache();
    public function getDescriptor();
    public function getId();
    public function transitionId();
    public function getNamespace($namespace);
    public function destroy();

    public function hasRecallKey(RecallKey $key);
    public function perpetuateRecall(user\IClient $client, RecallKey $lastKey=null);
}

interface IHandler extends core\IValueMap, \ArrayAccess {
    public function setLifeTime($lifeTime);
    public function getLifeTime();

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
    public function clearForAll();
    public function prune($age=7200);

    public function __set($key, $value);
    public function __get($key);
    public function __isset($key);
    public function __unset($key);
    
    public function getLastUpdated();
}

interface IBackend {
    public function setLifeTime($lifeTime);
    public function getLifeTime();
    
    public function insertDescriptor(IDescriptor $descriptor);
    public function fetchDescriptor($id, $transitionTime);
    public function touchSession(IDescriptor $descriptor);
    public function applyTransition(IDescriptor $descriptor);
    public function killSession(IDescriptor $descriptor);
    public function idExists($id);
    
    public function getNamespaceKeys(IDescriptor $descriptor, $namespace);
    public function pruneNamespace(IDescriptor $descriptor, $namespace, $age);
    public function clearNamespace(IDescriptor $descriptor, $namespace);
    public function clearNamespaceForAll($namespace);
    
    public function fetchNode(IDescriptor $descriptor, $namespace, $key);
    public function fetchLastUpdatedNode(IDescriptor $descriptor, $namespace);
    public function lockNode(IDescriptor $descriptor, \stdClass $node);
    public function unlockNode(IDescriptor $descriptor, \stdClass $node);
    public function updateNode(IDescriptor $descriptor, \stdClass $node);
    public function removeNode(IDescriptor $descriptor, $namespace, $key);
    public function hasNode(IDescriptor $descriptor, $namespace, $key);
    public function collectGarbage();

    public function generateRecallKey(user\IClient $client);
    public function hasRecallKey(RecallKey $key);
    public function destroyRecallKey(RecallKey $key);
    public function purgeRecallKeys();
}


class RecallKey {

    public $userId;
    public $key;

    public static function generate($userId) {
        return new self($userId, core\string\Generator::sessionId());
    }

    public function __construct($userId, $key) {
        $this->userId = $userId;
        $this->key = $key;
    }

    public function getInterlaceKey() {
        return substr($this->key, 0, 20).$this->userId.substr($this->key, 20);
    }
}


interface IDescriptor extends core\IArrayInterchange, opal\query\IDataRowProvider {
    public function isNew();
    public function hasJustStarted($flag=null);
    
    public function setInternalId($id);
    public function getInternalId();
    public function getInternalIdHex();
    public function setExternalId($id);
    public function getExternalId();
    public function getExternalIdHex();
    
    public function setTransitionId($id);
    public function getTransitionId();
    public function getTransitionIdHex();
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


interface IPerpetuator {
    public function setLifeTime($lifeTime);
    public function getLifeTime();
    
    public function getInputId();
    public function canRecallIdentity();

    public function perpetuate(IController $controller, IDescriptor $descriptor);
    public function destroy(IController $controller);

    public function perpetuateRecallKey(IController $controller, RecallKey $key);
    public function getRecallKey(IController $controller);
    public function destroyRecallKey(IController $controller);
}