<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\session;

use df;
use df\core;
use df\user;

class Handler implements user\session\IHandler, core\IDumpable {
    
    use core\TValueMap;

    protected $_namespace;    
    protected $_nodes = array();
    protected $_manager;
    protected $_lifeTime = null;
    
    public static function createNode($namespace, $key, $res, $locked=false) {
        $output = new \stdClass();
        $output->namespace = $namespace;
        $output->key = $key;
        $output->value = null;
        $output->creationTime = null;
        $output->updateTime = time();
        
        
        if($res !== null) {
            if($res['value'] !== null) {
                $output->value = unserialize($res['value']);
            }
            
            if(!empty($res['creationTime'])) {
                $output->creationTime = (int)$res['creationTime'];
            }
            
            if(!empty($res['updateTime'])) {
                $output->updateTime = (int)$res['updateTime'];
            }
        }
            
        $output->isLocked = (bool)$locked;
        return $output;
    }
    
    public function __construct(user\IManager $manager, $namespace) {
        $this->_manager = $manager;
        $this->_namespace = $namespace;
        
        if(empty($namespace)) {
            throw new user\InvalidArgumentException(
                'Invalid empty namespace'
            );
        }
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

    
    public function getSessionDescriptor() {
        return $this->_manager->getSessionDescriptor();
    }
    
    public function getSessionId() {
        return $this->_manager->getSessionId();
    }
    
    public function transitionSessionId() {
        $this->_manager->transitionSessionId();
        return $this;
    }
    
    public function isSessionOpen() {
        return $this->_manager->isSessionOpen();
    }
    
    
    
    public function acquire($key) {
        $descriptor = $this->_manager->getSessionDescriptor();
        
        if(!isset($this->_nodes[$key])) {
            $this->__get($key);
        }
        
        $this->_manager->getSessionCache()->removeNode($descriptor, $this->_namespace, $key);
        
        if(!$this->_nodes[$key]->isLocked) {
            $this->_manager->getSessionBackend()->lockNode(
                $descriptor, $this->_nodes[$key]
            );
        }
        
        return $this;
    }
    
    public function release($key) {
        if(isset($this->_nodes[$key]) && $this->_nodes[$key]->isLocked) {
            $descriptor = $this->_manager->getSessionDescriptor();
            $this->_manager->getSessionBackend()->unlockNode($descriptor, $this->_nodes[$key]);
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
        $descriptor = $this->_manager->getSessionDescriptor();
        return $this->_manager->getSessionBackend()->getNamespaceKeys($descriptor, $this->_namespace);
    }

    public function clear() {
        $descriptor = $this->_manager->getSessionDescriptor();
        
        $this->_manager->getSessionBackend()->clearNamespace($descriptor, $this->_namespace);
        $this->_manager->getSessionCache()->clear();
        $this->_nodes = array();
        
        return $this;
    }

    public function clearForAll() {
        $this->_manager->getSessionBackend()->clearNamespaceForAll($this->_namespace);
        $this->_manager->getSessionCache()->clear();
        $this->_nodes = array();

        return $this;
    }
    
    public function prune($age=7200) {
        $age = (int)$age;
        
        if($age < 1) {
            return $this;
        }
        
        $descriptor = $this->_manager->getSessionDescriptor();
        $this->_manager->getSessionBackend()->pruneNamespace($descriptor, $this->_namespace, $age);
        $this->_manager->getSessionCache()->clear();
        
        return $this;
    }
    
    
    public function __set($key, $value) {
        return $this->set($key, $value);
    }
    
    public function __get($key) {
        if(!isset($this->_nodes[$key])) {
            $descriptor = $this->_manager->getSessionDescriptor();
            $cache = $this->_manager->getSessionCache();
            
            if(!$node = $cache->fetchNode($descriptor, $this->_namespace, $key)) {
                $node = $this->_manager->getSessionBackend()->fetchNode(
                    $descriptor, $this->_namespace, $key
                );
                
                if($node->creationTime) {
                    $cache->insertNode($descriptor, $node);
                }
            } else if(!$node->creationTime) {
                // new node has been cached - make it not be new :)
                
                $node->creationTime = time();
                $cache->insertNode($descriptor, $node);
            }
            
            $this->_nodes[$key] = $node;
        }

        if($this->_lifeTime !== null && time() - $this->_nodes[$key]->updateTime > $this->_lifeTime) {
            $this->remove($key);
            $this->_nodes[$key] = self::createNode($this->_namespace, $key, null);
        }
        
        return $this->_nodes[$key];
    }
    
    public function __isset($key) {
        return $this->has($key);
    }
    
    public function __unset($key) {
        return $this->remove($key);
    }
    
    public function set($key, $value) {
        $atomicLock = false;
        
        if(!isset($this->_nodes[$key]) || !$this->_nodes[$key]->isLocked) {
            $atomicLock = true;
            $this->acquire($key);
        }
        
        $node = $this->_nodes[$key];
        $node->updateTime = time();
        $node->value = $value;
            
        $descriptor = $this->_manager->getSessionDescriptor();
        $this->_manager->getSessionCache()->insertNode($descriptor, $node);
        $this->_manager->getSessionBackend()->updateNode($descriptor, $node);
        
        
        if($atomicLock) {
            $this->release($key);
        }
        
        return $this;
    }
    
    public function get($key, $default=null) {
        $node = $this->__get($key);
        
        if($node->value !== null) {
            return $node->value;
        }
        
        return $default;
    }
    
    public function getLastUpdated() {
        $descriptor = $this->_manager->getSessionDescriptor();
        $node = $this->_manager->getSessionBackend()->fetchLastUpdatedNode($descriptor, $this->_namespace);
        
        if($node) {
            $this->_nodes[$node->key] = $node;
            return $node->value;
        }
        
        return null;
    }
    
    public function has($key) {
        if(isset($this->_nodes[$key])) {
            return true;
        }
        
        $descriptor = $this->_manager->getSessionDescriptor();
        return $this->_manager->getSessionBackend()->hasNode($descriptor, $this->_namespace, $key);
    }
    
    public function remove($key) {
        if(isset($this->_nodes[$key])) {
            if($this->_nodes[$key]->isLocked) {
                $this->release($key);
            }
            
            unset($this->_nodes[$key]);
        }
        
        $descriptor = $this->_manager->getSessionDescriptor();
        $this->_manager->getSessionBackend()->removeNode($descriptor, $this->_namespace, $key);
        $this->_manager->getSessionCache()->removeNode($descriptor, $this->_namespace, $key);
        
        return $this;
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
        $output = array();
        
        if($this->_manager->isSessionOpen()) {
            foreach($this->_nodes as $key => $node) {
                $output[$key] = $node->value;
            }
        }
        
        return $output;
    }
}
