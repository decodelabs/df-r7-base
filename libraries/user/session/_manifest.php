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
use df\flex;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface IController {

    const GC_PROBABILITY = 3;
    const TRANSITION_PROBABILITY = 10;
    const TRANSITION_LIFETIME = 30;
    const TRANSITION_COOLOFF = 20;

    public function isOpen();
    public function getId();
    public function transition();
    public function getBucket($namespace);
    public function destroy();
    public function getStartTime();

    public function hasRecallKey(RecallKey $key);
    public function perpetuateRecall(user\IClient $client, RecallKey $lastKey=null);
}

interface IBucket extends core\IValueMap, \ArrayAccess {
    public function getName();

    public function setLifeTime($lifeTime);
    public function getLifeTime();

    public function getDescriptor();
    public function getSessionId();
    public function isSessionOpen();

    public function refresh($key);
    public function refreshAll();
    public function getUpdateTime($id);
    public function getTimeSinceLastUpdate($key);

    public function getAllKeys();
    public function clear();
    public function clearForAll();
    public function prune($age=7200);

    public function getNode($key);
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

    public function getBucketKeys(IDescriptor $descriptor, $namespace);
    public function pruneBucket(IDescriptor $descriptor, $namespace, $age);
    public function clearBucket(IDescriptor $descriptor, $namespace);
    public function clearBucketForAll($namespace);

    public function fetchNode(IBucket $bucket, $key);
    public function fetchLastUpdatedNode(IBucket $bucket);
    public function updateNode(IBucket $bucket, INode $node);
    public function removeNode(IBucket $bucket, $key);
    public function hasNode(IBucket $bucket, $key);
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
        return new self($userId, flex\Generator::sessionId());
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
    public function hasJustStarted(bool $flag=null);

    public function setId($id);
    public function getId();
    public function getIdHex();
    public function setPublicKey($key);
    public function getPublicKey();
    public function getPublicKeyHex();

    public function setTransitionKey($key);
    public function getTransitionKey();
    public function getTransitionKeyHex();
    public function applyTransition($newPublicKey);

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
    public function getInputId();
    public function canRecallIdentity();

    public function perpetuate(IController $controller, IDescriptor $descriptor);
    public function destroy(IController $controller);
    public function handleDeadPublicKey($publicKey);

    public function perpetuateRecallKey(IController $controller, RecallKey $key);
    public function getRecallKey(IController $controller);
    public function destroyRecallKey(IController $controller);
}

interface INode {}

class Node implements INode {
    public $key;
    public $value;
    public $creationTime;
    public $updateTime;

    public static function create($key, $res, $locked=false) {
        $output = new self();
        $output->key = $key;
        $output->value = null;
        $output->creationTime = null;
        $output->updateTime = time();


        if($res !== null) {
            if($res['value'] !== null) {
                $output->value = unserialize($res['value']);
            }

            if(!empty($res['creationTime'])) {
                if($res['creationTime'] instanceof core\time\IDate) {
                    $res['creationTime'] = $res['creationTime']->toTimestamp();
                }

                $output->creationTime = (int)$res['creationTime'];
            }

            if(!empty($res['updateTime'])) {
                if($res['updateTime'] instanceof core\time\IDate) {
                    $res['updateTime'] = $res['updateTime']->toTimestamp();
                }

                $output->updateTime = (int)$res['updateTime'];
            }
        }

        return $output;
    }
}


interface ICache {
    public function insertDescriptor(IDescriptor $descriptor);
    public function fetchDescriptor($publicKey);
    public function removeDescriptor(IDescriptor $descriptor);

    public function fetchNode(IBucket $bucket, $key);
    public function insertNode(IBucket $bucket, INode $node);
    public function removeNode(IBucket $bucket, $key);

    public function setGlobalKeyringTimestamp();
    public function shouldRegenerateKeyring($keyringTimestamp);
}