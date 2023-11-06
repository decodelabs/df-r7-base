<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\helper;

use DecodeLabs\Genesis;
use df\axis;
use df\flex;

use df\user;

class Session extends Base implements user\session\IController
{
    protected static $_gcProbability = 3;
    protected static $_transitionsEnabled = true;
    protected static $_transitionProbability = 10;
    protected static $_transitionLifeTime = 30;
    protected static $_transitionCooloff = 20;

    public $descriptor;
    public $perpetuator;
    public $backend;
    public $cache;

    protected $_isOpen = false;
    protected $_buckets = [];

    public function __construct(user\IManager $manager)
    {
        parent::__construct($manager);
        $this->_open();
    }


    protected function _open()
    {
        $this->_isOpen = true;

        $this->cache = user\session\Cache::getInstance();
        $this->backend = axis\Model::factory('session');

        if (!$this->backend instanceof user\session\IBackend) {
            throw Exceptional::{'df/user/session/Logic'}(
                'Session model does not implement user\\session\\IBackend'
            );
        }

        switch (Genesis::$kernel->getMode()) {
            case 'Http':
                $this->perpetuator = new user\session\perpetuator\Cookie();
                break;

            default:
                $this->perpetuator = new user\session\perpetuator\BlackHole();
                break;
        }

        $publicKey = $this->perpetuator->getInputId();

        if (empty($publicKey)) {
            $this->descriptor = $this->_start();
        } else {
            $this->descriptor = $this->_resume($publicKey);
        }

        $this->perpetuator->perpetuate($this, $this->descriptor);

        if ((mt_rand() % 100) < self::$_gcProbability) {
            $this->backend->collectGarbage();
            $this->backend->purgeRecallKeys();
        }

        if (self::$_transitionsEnabled &&
            (!$this->descriptor->hasJustTransitioned(120) ||
            (mt_rand() % 100) < self::$_transitionProbability)) {
            $this->transition();
        }

        if ($this->descriptor->needsTouching(self::$_transitionLifeTime)) {
            $this->backend->touchSession($this->descriptor, self::$_transitionLifeTime);
            $this->cache->insertDescriptor($this->descriptor);
        }
    }


    protected function _start()
    {
        $time = time();
        $publicKey = $this->_generateId();

        $descriptor = new user\session\Descriptor($publicKey, $publicKey);
        $descriptor->setStartTime($time);
        $descriptor->setAccessTime($time);

        $descriptor = $this->backend->insertDescriptor($descriptor);
        $descriptor->hasJustStarted(true);

        $this->cache->insertDescriptor($descriptor);

        return $descriptor;
    }

    protected function _resume($publicKey)
    {
        $descriptor = $this->cache->fetchDescriptor($publicKey);

        if (!$descriptor) {
            $descriptor = $this->backend->fetchDescriptor(
                $publicKey,
                time() - self::$_transitionLifeTime
            );

            if ($descriptor) {
                $this->cache->insertDescriptor($descriptor);
            }
        }

        if ($descriptor === null) {
            $this->perpetuator->handleDeadPublicKey($publicKey);
            return $this->_start();
        }

        if (!$descriptor->hasJustTransitioned(self::$_transitionLifeTime)) {
            $descriptor->transitionKey = null;
        }

        // TODO: check accessTime is within perpetuator life time

        return $descriptor;
    }



    public function isOpen()
    {
        return $this->_isOpen;
    }

    public function getId(): string
    {
        return $this->descriptor->getPublicKey();
    }

    public function transition()
    {
        if (!self::$_transitionsEnabled ||
            $this->descriptor->hasJustStarted() ||
            $this->descriptor->hasJustTransitioned(self::$_transitionCooloff)) {
            return $this;
        }

        $this->cache->removeDescriptor($this->descriptor);
        $this->descriptor->applyTransition($this->_generateId());
        $this->backend->applyTransition($this->descriptor);
        $this->perpetuator->perpetuate($this, $this->descriptor);

        return $this;
    }

    public function setUserId(?string $id)
    {
        $this->descriptor->setUserId($id);
        $this->backend->applyTransition($this->descriptor);
        return $this;
    }

    protected function _generateId()
    {
        do {
            $output = flex\Generator::sessionId(true);
        } while ($this->backend->idExists($output));

        return $output;
    }

    public function getStartTime()
    {
        return $this->descriptor->startTime;
    }


    // Handlers
    public function getBucket($name)
    {
        if (!$this->_isOpen) {
            throw Exceptional::{'df/user/session/Logic'}(
                'Cannot get a session bucket once the session has been destroyed'
            );
        }

        if (!isset($this->_buckets[$name])) {
            $this->_buckets[$name] = new user\session\Bucket($this, $name);
        }

        return $this->_buckets[$name];
    }



    public function getBuckets(): array
    {
        if (!$this->_isOpen) {
            throw Exceptional::{'df/user/session/Logic'}(
                'Cannot get a session bucket once the session has been destroyed'
            );
        }

        return $this->backend->getBuckets($this->descriptor);
    }

    public function getBucketsLike(string $bucket, string $operator = null): array
    {
        if (!$this->_isOpen) {
            throw Exceptional::{'df/user/session/Logic'}(
                'Cannot get a session bucket once the session has been destroyed'
            );
        }

        return $this->backend->getBucketsLike($this->descriptor, $bucket, $operator);
    }

    public function getBucketsForUserLike(string $userId, string $bucket, string $operator = null): array
    {
        if (!$this->_isOpen) {
            throw Exceptional::{'df/user/session/Logic'}(
                'Cannot get a session bucket once the session has been destroyed'
            );
        }

        return $this->backend->getBucketsForUserLike($userId, $bucket, $operator);
    }

    public function getBucketsForAllLike(string $bucket, string $operator = null): array
    {
        if (!$this->_isOpen) {
            throw Exceptional::{'df/user/session/Logic'}(
                'Cannot get a session bucket once the session has been destroyed'
            );
        }

        return $this->backend->getBucketsForAllLike($bucket, $operator);
    }



    public function clearBuckets(string $bucket, string $operator = null)
    {
        if (!$this->_isOpen) {
            throw Exceptional::{'df/user/session/Logic'}(
                'Cannot get a session bucket once the session has been destroyed'
            );
        }

        $this->backend->clearBucket($this->descriptor, $bucket, $operator);
        return $this;
    }

    public function clearBucketsForUser(string $userId, string $bucket, string $operator = null)
    {
        if (!$this->_isOpen) {
            throw Exceptional::{'df/user/session/Logic'}(
                'Cannot get a session bucket once the session has been destroyed'
            );
        }

        $this->backend->clearBucket($userId, $bucket, $operator);
        return $this;
    }

    public function clearBucketsForAll(string $bucket, string $operator = null)
    {
        if (!$this->_isOpen) {
            throw Exceptional::{'df/user/session/Logic'}(
                'Cannot get a session bucket once the session has been destroyed'
            );
        }

        $this->backend->clearBucketForAll($bucket, $operator);
        return $this;
    }



    public function destroy(bool $restart = false)
    {
        if ($this->perpetuator) {
            $key = $this->perpetuator->getRecallKey($this);
            $this->perpetuator->destroy($this);

            if ($key) {
                $this->backend->destroyRecallKey($key);
            }
        }

        $this->cache->removeDescriptor($this->descriptor);
        $this->backend->killSession($this->descriptor);
        $this->descriptor = null;
        $this->_buckets = [];
        $this->_isOpen = false;

        $this->manager->clearClient();

        if ($restart) {
            $this->_open();
        }

        return $this;
    }

    // Recall
    public function hasRecallKey(user\session\RecallKey $key)
    {
        return $this->backend->hasRecallKey($key);
    }

    public function perpetuateRecall(user\IClient $client, user\session\RecallKey $lastKey = null)
    {
        if ($lastKey) {
            $this->backend->destroyRecallKey($lastKey);
        }

        if ($this->perpetuator) {
            $this->perpetuator->perpetuateRecallKey(
                $this,
                $this->backend->generateRecallKey($client)
            );
        }

        return $this;
    }
}
