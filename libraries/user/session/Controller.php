<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\session;

use df;
use df\core;
use df\user;
use df\axis;
use df\flex;

class Controller implements IController {

    const GC_PROBABILITY = 3;
    const TRANSITION_PROBABILITY = 10;
    const TRANSITION_LIFETIME = 10;
    const TRANSITION_COOLOFF = 20;

    protected $_descriptor;
    protected $_perpetuator;
    protected $_backend;
    protected $_cache;
    protected $_isOpen = false;
    protected $_buckets = [];

    public function isOpen() {
        return $this->_isOpen;
    }

// Perpetuator
    public function setPerpetuator(IPerpetuator $perpetuator) {
        if($this->_isOpen) {
            throw new RuntimeException(
                'Cannot set session perpetuator, the session has already started'
            );
        }

        $this->_perpetuator = $perpetuator;
        return $this;
    }

    public function getPerpetuator() {
        $this->_open();
        return $this->_perpetuator;
    }

    protected function _loadPerpetuator() {
        switch(df\Launchpad::$application->getRunMode()) {
            case 'Http':
                $this->_perpetuator = new user\session\perpetuator\Cookie($this);
                break;

            default:
                $this->_perpetuator = new user\session\perpetuator\Shell($this);
                break;
        }
    }


// Backend
    public function setBackend(IBackend $backend) {
        if($this->_isOpen) {
            throw new RuntimeException(
                'Cannot set session backend, the session has already started'
            );
        }

        $this->_backend = $backend;
        return $this;
    }

    public function getBackend() {
        $this->_open();
        return $this->_backend;
    }

    protected function _loadBackend() {
        $this->_backend = $this->_getManager()->getSessionBackend();
    }


// Cache
    public function getCache() {
        $this->_open();
        return $this->_cache;
    }


// Descriptor
    public function getDescriptor() {
        $this->_open();
        return $this->_descriptor;
    }

    public function getId() {
        return $this->getDescriptor()->getExternalId();
    }

    public function transitionId() {
        $this->_open();

        if($this->_descriptor->hasJustStarted()
        || $this->_descriptor->hasJustTransitioned(self::TRANSITION_COOLOFF)) {
            return $this;
        }

        $this->_cache->removeDescriptor($this->_descriptor);
        $this->_descriptor->applyTransition($this->_generateId());
        $this->_backend->applyTransition($this->_descriptor);
        $this->_perpetuator->perpetuate($this, $this->_descriptor);

        return $this;
    }

    public function setUserId($id) {
        $this->_open();
        $this->_descriptor->setUserId($id);
        $this->_backend->applyTransition($this->_descriptor);
        return $this;
    }

    protected function _generateId() {
        do {
            $output = flex\Generator::sessionId(true);
        } while($this->_backend->idExists($output));

        return $output;
    }


// Handlers
    protected function _open() {
        if($this->_isOpen && $this->_descriptor) {
            return;
        }

        $this->_isOpen = true;

        if($this->_cache === null) {
            $this->_cache = Cache::getInstance();
        }

        if($this->_backend === null) {
            $this->_loadBackend();
        }

        if($this->_perpetuator === null) {
            $this->_loadPerpetuator();
        }

        $externalId = $this->_perpetuator->getInputId();

        if(empty($externalId)) {
            $this->_descriptor = $this->_start();
        } else {
            $this->_descriptor = $this->_resume($externalId);
        }

        $this->_perpetuator->perpetuate($this, $this->_descriptor);

        if((mt_rand() % 100) < self::GC_PROBABILITY) {
            $this->_backend->collectGarbage();
            $this->_backend->purgeRecallKeys();
        }

        if(!$this->_descriptor->hasJustTransitioned(120)
        || ((mt_rand() % 100) < self::TRANSITION_PROBABILITY)) {
            $this->transitionId();
        }

        if($this->_descriptor->needsTouching(self::TRANSITION_LIFETIME)) {
            $this->_backend->touchSession($this->_descriptor);
            $this->_cache->insertDescriptor($this->_descriptor);
        }
    }


    protected function _start() {
        $time = time();
        $externalId = $this->_generateId();

        $descriptor = new Descriptor($externalId, $externalId);
        $descriptor->setStartTime($time);
        $descriptor->setAccessTime($time);

        $descriptor = $this->_backend->insertDescriptor($descriptor);
        $descriptor->hasJustStarted(true);

        $this->_cache->insertDescriptor($descriptor);

        return $descriptor;
    }

    protected function _resume($externalId) {
        $descriptor = $this->_cache->fetchDescriptor($externalId);

        if(!$descriptor) {
            $descriptor = $this->_backend->fetchDescriptor(
                $externalId, time() - self::TRANSITION_LIFETIME
            );

            if($descriptor) {
                $this->_cache->insertDescriptor($descriptor);
            }
        }

        $this->_perpetuator->handleDeadExternalId($externalId);

        if($descriptor === null) {
            return $this->_start();
        }

        if(!$descriptor->hasJustTransitioned(self::TRANSITION_LIFETIME)) {
            $descriptor->transitionId = null;
        }

        // TODO: check accessTime is within perpetuator life time

        return $descriptor;
    }




    public function __get($name) {
        return $this->getBucket($name);
    }

    public function getBucket($name) {
        if(!isset($this->_buckets[$name])) {
            $this->_buckets[$name] = new Bucket($this, $name);
        }

        return $this->_buckets[$name];
    }

    public function destroy() {
        $this->_open();

        if($this->_perpetuator) {
            $key = $this->_perpetuator->getRecallKey($this);
            $this->_perpetuator->destroy($this);

            if($key) {
                $this->_backend->destroyRecallKey($key);
            }
        }

        $this->_cache->removeDescriptor($this->_descriptor);
        $this->_backend->killSession($this->_descriptor);
        $this->_descriptor = null;
        $this->_buckets = [];
        $this->_isOpen = false;

        $this->_getManager()->clearClient();
        return $this;
    }

// Recall
    public function hasRecallKey(RecallKey $key) {
        $this->_open();
        return $this->_backend->hasRecallKey($key);
    }

    public function perpetuateRecall(user\IClient $client, RecallKey $lastKey=null) {
        $this->_open();

        if($lastKey) {
            $this->_backend->destroyRecallKey($lastKey);
        }

        if($this->_perpetuator) {
            $this->_perpetuator->perpetuateRecallKey(
                $this,
                $this->_backend->generateRecallKey($client)
            );
        }

        return $this;
    }

// Helpers
    private function _getManager() {
        return user\Manager::getInstance();
    }
}