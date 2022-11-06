<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\helper;

use df;
use df\core;
use df\user;
use df\mesh;

use DecodeLabs\Glitch\Dumpable;

class Options extends Base implements
    user\ISessionBackedHelper,
    core\IValueMap,
    mesh\event\IListener,
    Dumpable
{
    use core\TValueMap;
    use user\TSessionBackedHelper;

    protected function _generateDefaultSessionData()
    {
        if ($this->manager->isLoggedIn()) {
            return $this->manager->getUserModel()->fetchClientOptions(
                $this->manager->getId()
            );
        }

        return [];
    }

    public function set($key, $value)
    {
        $this->_ensureSessionData();

        if (is_bool($value)) {
            $value = (int)$value;
        }

        $this->_sessionData[$key] = $value;
        $this->_sessionDataChanged = true;

        if ($this->manager->isLoggedIn()) {
            $this->manager->getUserModel()->updateClientOptions(
                $this->manager->getId(),
                [$key => $value]
            );
        }

        return $this;
    }

    public function get($key, $default=null)
    {
        $this->_ensureSessionData();

        if (isset($this->_sessionData[$key])) {
            return $this->_sessionData[$key];
        } else {
            return $default;
        }
    }

    public function has(...$keys)
    {
        $this->_ensureSessionData();

        foreach ($keys as $key) {
            if (isset($this->_sessionData[$key])) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$keys)
    {
        $this->_ensureSessionData();

        foreach ($keys as $key) {
            unset($this->_sessionData[$key]);
            $this->_sessionDataChanged = true;
        }

        if ($this->manager->isLoggedIn()) {
            $this->manager->getUserModel()->removeClientOptions(
                $this->manager->getId(),
                $keys
            );
        }

        return $this;
    }

    public function import(array $options)
    {
        $this->_ensureSessionData();
        $this->_sessionData = array_merge($this->_sessionData, $options);
        $this->_sessionDataChanged = true;

        if ($this->manager->isLoggedIn()) {
            $this->manager->getUserModel()->updateClientOptions(
                $this->manager->getId(),
                $options
            );
        }

        return $this;
    }

    public function refresh()
    {
        $this->_destroySessionData();
        return $this;
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


    // Events
    public function handleEvent(mesh\event\IEvent $event)
    {
        switch ($event->getAction()) {
            case 'authenticate':
            case 'recall':
            case 'logout':
                $this->refresh();
                break;
        }

        return $this;
    }
}
