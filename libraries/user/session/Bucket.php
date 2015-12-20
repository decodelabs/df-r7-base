<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\session;

use df;
use df\core;
use df\user;

class Bucket implements user\session\IBucket, core\IDumpable {

    use core\TValueMap;


    protected $_name;
    protected $_nodes = [];
    protected $_controller;
    protected $_lifeTime = null;

    public function __construct(user\session\IController $controller, $name) {
        $this->_controller = $controller;
        $this->_name = $name;

        if(empty($name)) {
            throw new user\InvalidArgumentException(
                'Invalid empty name bucket name'
            );
        }
    }

    public function getName() {
        return $this->_name;
    }

    public function setLifeTime($lifeTime) {
        $this->_lifeTime = (int)$lifeTime;

        if($this->_lifeTime <= 0) {
            $this->_lifeTime = null;
        }

        return $this;
    }

    public function getLifeTime() {
        return $this->_lifeTime;
    }


    public function getDescriptor() {
        return $this->_controller->descriptor;
    }

    public function getSessionId() {
        return $this->_controller->getId();
    }

    public function transitionSessionId() {
        $this->_controller->transitionId();
        return $this;
    }

    public function isSessionOpen() {
        return $this->_controller->isOpen();
    }



    public function acquire($key) {
        if(!isset($this->_nodes[$key])) {
            $this->getNode($key);
        }

        $this->_controller->cache->removeNode($this, $key);

        if(!$this->_nodes[$key]->isLocked) {
            $this->_controller->backend->lockNode($this, $this->_nodes[$key]);
        }

        return $this;
    }

    public function release($key) {
        if(isset($this->_nodes[$key]) && $this->_nodes[$key]->isLocked) {
            $this->_controller->backend->unlockNode($this, $this->_nodes[$key]);
            $this->_nodes[$key]->isLocked = false;
        }

        return $this;
    }

    public function update($key, \Closure $func) {
        $this->acquire($key);
        $node = $this->_nodes[$key];

        $value = $func($node->value);
        $this->set($key, $value);

        return $this->release($key);
    }

    public function refresh($key) {
        if(isset($this->_nodes[$key]) && $this->_nodes[$key]->isLocked) {
            // skip this
        } else {
            unset($this->_nodes[$key]);
        }

        return $this;
    }

    public function refreshAll() {
        foreach($this->_nodes as $key => $node) {
            if(!$node->isLocked) {
                unset($this->_nodes[$key]);
            }
        }

        return $this;
    }

    public function getUpdateTime($key) {
        return $this->_get($key)->updateTime;
    }

    public function getTimeSinceLastUpdate($key) {
        return time() - $this->getUpdateTime($key);
    }


    public function getAllKeys() {
        return $this->_controller->backend->getBucketKeys(
            $this->_controller->descriptor, $this->_name
        );
    }

    public function clear() {
        $this->_controller->backend->clearBucket(
            $this->_controller->descriptor, $this->_name
        );

        $this->_controller->cache->clear();
        $this->_nodes = [];

        return $this;
    }

    public function clearForAll() {
        $this->_controller->backend->clearBucketForAll($this->_name);
        $this->_controller->cache->clear();
        $this->_nodes = [];

        return $this;
    }

    public function prune($age=7200) {
        $age = (int)$age;

        if($age < 1) {
            return $this;
        }

        $this->_controller->backend->pruneBucket(
            $this->_controller->descriptor, $this->_name, $age
        );

        $this->_controller->cache->clear();
        return $this;
    }

    public function getNode($key) {
        $key = (string)$key;

        if(!isset($this->_nodes[$key])) {
            $descriptor = $this->_controller->descriptor;
            $cache = $this->_controller->cache;

            if(!$node = $cache->fetchNode($this, $key)) {
                $node = $this->_controller->backend->fetchNode($this, $key);

                if($node->creationTime) {
                    $cache->insertNode($this, $node);
                }
            } else if(!$node->creationTime) {
                // new node has been cached - make it not be new :)

                $node->creationTime = time();
                $cache->insertNode($this, $node);
            }

            $this->_nodes[$key] = $node;
        }

        if($this->_lifeTime !== null && time() - $this->_nodes[$key]->updateTime > $this->_lifeTime) {
            $this->remove($key);
            $this->_nodes[$key] = Node::create($key, null);
        }

        return $this->_nodes[$key];
    }


    public function set($key, $value) {
        $key = (string)$key;
        $atomicLock = false;

        if(!isset($this->_nodes[$key]) || !$this->_nodes[$key]->isLocked) {
            $atomicLock = true;
            $this->acquire($key);
        }

        $node = $this->_nodes[$key];
        $node->updateTime = time();
        $node->value = $value;

        $this->_controller->cache->insertNode($this, $node);
        $this->_controller->backend->updateNode($this, $node);

        if($atomicLock) {
            $this->release($key);
        }

        return $this;
    }

    public function get($key, $default=null) {
        $node = $this->getNode($key);

        if($node->value !== null) {
            return $node->value;
        }

        return $default;
    }

    public function getLastUpdated() {
        $node = $this->_controller->backend->fetchLastUpdatedNode($this);

        if($node) {
            $this->_nodes[$node->key] = $node;
            return $node->value;
        }

        return null;
    }

    public function has($key) {
        $key = (string)$key;

        if(isset($this->_nodes[$key])) {
            return true;
        }

        return $this->_controller->backend->hasNode($this, $key);
    }

    public function remove($key) {
        $key = (string)$key;

        if(isset($this->_nodes[$key])) {
            if($this->_nodes[$key]->isLocked) {
                $this->release($key);
            }

            unset($this->_nodes[$key]);
        }

        $this->_controller->backend->removeNode($this, $key);
        $this->_controller->cache->removeNode($this, $key);

        return $this;
    }

    public function __set($key, $value) {
        return $this->set($key, $value);
    }

    public function __get($key) {
        return $this->getNode($key);
    }

    public function __isset($key) {
        return $this->has($key);
    }

    public function __unset($key) {
        return $this->remove($key);
    }


    public function offsetSet($key, $value) {
        return $this->set($key, $value);
    }

    public function offsetGet($key) {
        return $this->get($key);
    }

    public function offsetExists($key) {
        return $this->has($key);
    }

    public function offsetUnset($key) {
        return $this->remove($key);
    }


// Dump
    public function getDumpProperties() {
        $output = [];

        if($this->_controller->isOpen()) {
            foreach($this->_nodes as $key => $node) {
                $output[$key] = $node->value;
            }
        }

        return $output;
    }
}
