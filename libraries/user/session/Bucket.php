<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\session;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch\Dumpable;

use df\core;
use df\user;

class Bucket implements user\session\IBucket, Dumpable
{
    use core\TValueMap;

    protected $_name;
    protected $_nodes = [];
    protected $_controller;
    protected $_lifeTime = null;

    public function __construct(user\session\IController $controller, string $name)
    {
        $this->_controller = $controller;
        $this->_name = $name;

        if (empty($name)) {
            throw Exceptional::InvalidArgument(
                'Invalid empty name bucket name'
            );
        }
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function setLifeTime($lifeTime)
    {
        $this->_lifeTime = (int)$lifeTime;

        if ($this->_lifeTime <= 0) {
            $this->_lifeTime = null;
        }

        return $this;
    }

    public function getLifeTime()
    {
        return $this->_lifeTime;
    }


    public function getDescriptor()
    {
        return $this->_controller->descriptor;
    }

    public function getSessionId()
    {
        return $this->_controller->getId();
    }

    public function isSessionOpen()
    {
        return $this->_controller->isOpen();
    }

    public function refresh($key)
    {
        unset($this->_nodes[$key]);
        return $this;
    }

    public function refreshAll()
    {
        $this->_nodes = [];
        return $this;
    }

    public function getUpdateTime($key)
    {
        return $this->__get($key)->updateTime;
    }

    public function getTimeSinceLastUpdate($key)
    {
        return time() - $this->getUpdateTime($key);
    }


    public function getAllKeys()
    {
        return $this->_controller->backend->getBucketKeys(
            $this->_controller->descriptor,
            $this->_name
        );
    }

    public function clear()
    {
        $this->_controller->backend->clearBucket(
            $this->_controller->descriptor,
            $this->_name
        );

        $this->_controller->cache->clear();
        $this->_nodes = [];

        return $this;
    }

    public function clearForUser($userId)
    {
        $this->_controller->backend->clearBucketForUser($userId, $this->_name);
        $this->_controller->cache->clear();
        $this->_nodes = [];

        return $this;
    }

    public function clearForClient()
    {
        $manager = user\Manager::getInstance();

        if ($manager->isLoggedIn()) {
            $this->clearForUser($manager->getId());
        } else {
            $this->clear();
        }

        return $this;
    }

    public function clearForAll()
    {
        $this->_controller->backend->clearBucketForAll($this->_name);
        $this->_controller->cache->clear();
        $this->_nodes = [];

        return $this;
    }

    public function prune($age = 7200)
    {
        $age = (int)$age;

        if ($age < 1) {
            return $this;
        }

        $this->_controller->backend->pruneBucket(
            $this->_controller->descriptor,
            $this->_name,
            $age
        );

        $this->_controller->cache->clear();
        return $this;
    }

    public function getNode($key)
    {
        $key = (string)$key;

        if (!isset($this->_nodes[$key])) {
            $descriptor = $this->_controller->descriptor;
            $cache = $this->_controller->cache;

            if (!$node = $cache->fetchNode($this, $key)) {
                $node = $this->_controller->backend->fetchNode($this, $key);

                if ($node->creationTime) {
                    $cache->insertNode($this, $node);
                }
            } elseif (!$node->creationTime) {
                // new node has been cached - make it not be new :)

                $node->creationTime = time();
                $cache->insertNode($this, $node);
            }

            $this->_nodes[$key] = $node;
        }

        if ($this->_lifeTime !== null && time() - $this->_nodes[$key]->updateTime > $this->_lifeTime) {
            $this->remove($key);
            $this->_nodes[$key] = Node::create($key, null);
        }

        return $this->_nodes[$key];
    }


    public function set($key, $value)
    {
        $node = $this->getNode($key);
        $node->updateTime = time();
        $node->value = $value;

        $this->_controller->cache->insertNode($this, $node);
        $this->_controller->backend->updateNode($this, $node);

        return $this;
    }

    public function get($key, $default = null)
    {
        $node = $this->getNode($key);

        if ($node->value !== null) {
            return $node->value;
        }

        return $default;
    }

    public function getLastUpdated()
    {
        $node = $this->_controller->backend->fetchLastUpdatedNode($this);

        if ($node) {
            $this->_nodes[$node->key] = $node;
            return $node->value;
        }

        return null;
    }

    public function has(...$keys)
    {
        foreach ($keys as $key) {
            $key = (string)$key;

            if (isset($this->_nodes[$key])) {
                return true;
            }

            if ($this->_controller->backend->hasNode($this, $key)) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$keys)
    {
        foreach ($keys as $key) {
            $key = (string)$key;
            unset($this->_nodes[$key]);
            $this->_controller->backend->removeNode($this, $key);
            $this->_controller->cache->removeNode($this, $key);
        }

        return $this;
    }

    public function __set($key, $value): void
    {
        $this->set($key, $value);
    }

    public function __get($key)
    {
        return $this->getNode($key);
    }

    public function __isset($key)
    {
        return $this->has($key);
    }

    public function __unset($key): void
    {
        $this->remove($key);
    }


    public function offsetSet(
        mixed $key,
        mixed $value
    ): void {
        $this->set($key, $value);
    }

    public function offsetGet(mixed $key): mixed
    {
        return $this->get($key);
    }

    public function offsetExists(mixed $key): bool
    {
        return $this->has($key);
    }

    public function offsetUnset(mixed $key): void
    {
        $this->remove($key);
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        if ($this->_controller->isOpen()) {
            foreach ($this->_nodes as $key => $node) {
                yield 'value:' . $key => $node->value;
            }
        }
    }
}
