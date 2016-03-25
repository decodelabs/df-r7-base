<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\helper;

use df;
use df\core;
use df\user;
use df\axis;
use df\flex;

class Session extends Base implements user\session\IController {

    public $descriptor;
    public $perpetuator;
    public $backend;
    public $cache;

    protected $_isOpen = false;
    protected $_buckets = [];

    public function __construct(user\IManager $manager) {
        parent::__construct($manager);
        $this->_open();
    }


    protected function _open() {
        $runMode = df\Launchpad::$application->getRunMode();
        $this->_isOpen = true;

        $this->cache = user\session\Cache::getInstance();
        $this->backend = axis\Model::factory('session');

        if(!$this->backend instanceof user\session\IBackend) {
            throw new user\session\LogicException(
                'Session model does not implement user\\session\\IBackend'
            );
        }

        switch($runMode) {
            case 'Http':
                $this->perpetuator = new user\session\perpetuator\Cookie($this);
                break;

            default:
                $this->perpetuator = new user\session\perpetuator\Shell($this);
                break;
        }

        $publicKey = $this->perpetuator->getInputId();

        if(empty($publicKey)) {
            $this->descriptor = $this->_start();
        } else {
            $this->descriptor = $this->_resume($publicKey);
        }

        $this->perpetuator->perpetuate($this, $this->descriptor);

        if((mt_rand() % 100) < self::GC_PROBABILITY) {
            $this->backend->collectGarbage();
            $this->backend->purgeRecallKeys();
        }

        if(!$this->descriptor->hasJustTransitioned(120)
        || ((mt_rand() % 100) < self::TRANSITION_PROBABILITY)) {
            $this->transition();
        }

        if($this->descriptor->needsTouching(self::TRANSITION_LIFETIME)) {
            $this->backend->touchSession($this->descriptor);
            $this->cache->insertDescriptor($this->descriptor);
        }
    }


    protected function _start() {
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

    protected function _resume($publicKey) {
        $descriptor = $this->cache->fetchDescriptor($publicKey);

        if(!$descriptor) {
            $descriptor = $this->backend->fetchDescriptor(
                $publicKey, time() - self::TRANSITION_LIFETIME
            );

            if($descriptor) {
                $this->cache->insertDescriptor($descriptor);
            }
        }

        if($descriptor === null) {
            $this->perpetuator->handleDeadPublicKey($publicKey);
            return $this->_start();
        }

        if(!$descriptor->hasJustTransitioned(self::TRANSITION_LIFETIME)) {
            $descriptor->transitionKey = null;
        }

        // TODO: check accessTime is within perpetuator life time

        return $descriptor;
    }



    public function isOpen() {
        return $this->_isOpen;
    }

    public function getId() {
        return $this->descriptor->getPublicKey();
    }

    public function transition() {
        if($this->descriptor->hasJustStarted()
        || $this->descriptor->hasJustTransitioned(self::TRANSITION_COOLOFF)) {
            return $this;
        }

        $this->cache->removeDescriptor($this->descriptor);
        $this->descriptor->applyTransition($this->_generateId());
        $this->backend->applyTransition($this->descriptor);
        $this->perpetuator->perpetuate($this, $this->descriptor);

        return $this;
    }

    public function setUserId($id) {
        $this->descriptor->setUserId($id);
        $this->backend->applyTransition($this->descriptor);
        return $this;
    }

    protected function _generateId() {
        do {
            $output = flex\Generator::sessionId(true);
        } while($this->backend->idExists($output));

        return $output;
    }

    public function getStartTime() {
        return $this->descriptor->startTime;
    }


// Handlers
    public function getBucket($name) {
        if(!$this->_isOpen) {
            throw new user\session\LogicException(
                'Cannot get a session bucket once the session has been destroyed'
            );
        }

        if(!isset($this->_buckets[$name])) {
            $this->_buckets[$name] = new user\session\Bucket($this, $name);
        }

        return $this->_buckets[$name];
    }

    public function destroy() {
        if($this->perpetuator) {
            $key = $this->perpetuator->getRecallKey($this);
            $this->perpetuator->destroy($this);

            if($key) {
                $this->backend->destroyRecallKey($key);
            }
        }

        $this->cache->removeDescriptor($this->descriptor);
        $this->backend->killSession($this->descriptor);
        $this->descriptor = null;
        $this->_buckets = [];
        $this->_isOpen = false;

        $this->manager->clearClient();
        return $this;
    }

// Recall
    public function hasRecallKey(user\session\RecallKey $key) {
        return $this->backend->hasRecallKey($key);
    }

    public function perpetuateRecall(user\IClient $client, user\session\RecallKey $lastKey=null) {
        if($lastKey) {
            $this->backend->destroyRecallKey($lastKey);
        }

        if($this->perpetuator) {
            $this->perpetuator->perpetuateRecallKey(
                $this,
                $this->backend->generateRecallKey($client)
            );
        }

        return $this;
    }
}