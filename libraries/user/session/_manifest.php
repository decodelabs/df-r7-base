<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\session;

use df\core;
use df\flex;
use df\user;

interface IController
{
    public const TRANSITION_COOLOFF = 20;

    public function isOpen();
    public function getId(): string;
    public function transition();

    public function getBucket($namespace);

    public function getBuckets(): array;
    public function getBucketsLike(string $bucket, string $operator = null): array;
    public function getBucketsForUserLike(string $userId, string $bucket, string $operator = null): array;
    public function getBucketsForAllLike(string $bucket, string $operator = null): array;

    public function clearBuckets(string $bucket, string $operator = null);
    public function clearBucketsForUser(string $userId, string $bucket, string $operator = null);
    public function clearBucketsForAll(string $bucket, string $operator = null);

    public function destroy(bool $restart = false);
    public function getStartTime();

    public function hasRecallKey(RecallKey $key);
    public function perpetuateRecall(user\IClient $client, RecallKey $lastKey = null);
}

interface IBucket extends core\IValueMap, \ArrayAccess
{
    public function getName(): string;

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
    public function clearForUser($userId);
    public function clearForClient();
    public function clearForAll();
    public function prune($age = 7200);

    public function getNode($key);
    public function __set($key, $value);
    public function __get($key);
    public function __isset($key);
    public function __unset($key): void;

    public function getLastUpdated();
}

interface IBackend
{
    public function setLifeTime($lifeTime);
    public function getLifeTime(): int;

    public function insertDescriptor(Descriptor $descriptor);
    public function fetchDescriptor(string $id, ?int $transitionTime): ?Descriptor;
    public function touchSession(Descriptor $descriptor, int $lifeTime = 30);
    public function applyTransition(Descriptor $descriptor);
    public function killSession(Descriptor $descriptor);
    public function idExists(string $id): bool;

    public function getBucketKeys(Descriptor $descriptor, string $bucket): array;
    public function pruneBucket(Descriptor $descriptor, string $bucket, int $age);

    public function getBuckets(Descriptor $descriptor): array;
    public function getBucketsLike(Descriptor $descriptor, string $bucket, string $operator = null): array;
    public function getBucketsForUserLike(string $userId, string $bucket, string $operator = null): array;
    public function getBucketsForAllLike(string $bucket, string $operator = null): array;

    public function clearBucket(Descriptor $descriptor, string $bucket, string $operator = null);
    public function clearBucketForUser(string $userId, string $bucket, string $operator = null);
    public function clearBucketForAll(string $bucket, string $operator = null);

    public function fetchNode(IBucket $bucket, $key): Node;
    public function fetchLastUpdatedNode(IBucket $bucket): ?Node;
    public function updateNode(IBucket $bucket, Node $node);
    public function removeNode(IBucket $bucket, string $key);
    public function hasNode(IBucket $bucket, string $key);
    public function collectGarbage();

    public function generateRecallKey(user\IClient $client);
    public function hasRecallKey(RecallKey $key): bool;
    public function destroyRecallKey(RecallKey $key);
    public function purgeRecallKeys();
}


class RecallKey
{
    public $userId;
    public $key;

    public static function generate($userId)
    {
        return new self($userId, flex\Generator::sessionId());
    }

    public function __construct($userId, $key)
    {
        $this->userId = $userId;
        $this->key = $key;
    }

    public function getInterlaceKey()
    {
        return substr($this->key, 0, 20) . $this->userId . substr($this->key, 20);
    }
}


interface IPerpetuator
{
    public function getInputId();
    public function canRecallIdentity();

    public function perpetuate(IController $controller, Descriptor $descriptor);
    public function destroy(IController $controller);
    public function handleDeadPublicKey($publicKey);

    public function perpetuateRecallKey(IController $controller, RecallKey $key);
    public function getRecallKey(IController $controller);
    public function destroyRecallKey(IController $controller);

    public function perpetuateState(user\IClient $client);
}

class Node
{
    public $key;
    public $value;
    public $creationTime;
    public $updateTime;

    public static function create($key, $res, $locked = false)
    {
        $output = new self();
        $output->key = $key;
        $output->value = null;
        $output->creationTime = null;
        $output->updateTime = time();


        if ($res !== null) {
            if ($res['value'] !== null) {
                $output->value = unserialize($res['value']);
            }

            if (!empty($res['creationTime'])) {
                if ($res['creationTime'] instanceof core\time\IDate) {
                    $res['creationTime'] = $res['creationTime']->toTimestamp();
                }

                $output->creationTime = (int)$res['creationTime'];
            }

            if (!empty($res['updateTime'])) {
                if ($res['updateTime'] instanceof core\time\IDate) {
                    $res['updateTime'] = $res['updateTime']->toTimestamp();
                }

                $output->updateTime = (int)$res['updateTime'];
            }
        }

        return $output;
    }
}


interface ICache
{
    public function insertDescriptor(Descriptor $descriptor);
    public function fetchDescriptor($publicKey);
    public function removeDescriptor(Descriptor $descriptor);

    public function fetchNode(IBucket $bucket, $key);
    public function insertNode(IBucket $bucket, Node $node);
    public function removeNode(IBucket $bucket, $key);

    public function setGlobalKeyringTimestamp();
    public function shouldRegenerateKeyring($keyringTimestamp);
}
