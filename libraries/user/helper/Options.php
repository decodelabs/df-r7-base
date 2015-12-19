<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\helper;

use df;
use df\core;
use df\user;

class Options extends Base implements user\ISessionBackedHelper, core\IValueMap, core\IDumpable {

    use core\TValueMap;
    use user\TSessionBackedHelper;

    protected function _generateDefaultSessionData() {
        if($this->manager->isLoggedIn()) {
            return $this->manager->getUserModel()->fetchClientOptions(
                $this->manager->client->getId()
            );
        }

        return [];
    }

    public function set($key, $value) {
        $this->_ensureSessionData();

        if(is_bool($value)) {
            $value = (int)$value;
        }

        $this->_sessionData[$key] = $value;

        if($this->manager->isLoggedIn()) {
            $this->manager->getUserModel()->updateClientOptions(
                $this->manager->client->getId(),
                [$key => $value]
            );
        }

        return $this;
    }

    public function get($key, $default=null) {
        $this->_ensureSessionData();

        if(isset($this->_sessionData[$key])) {
            return $this->_sessionData[$key];
        } else {
            return $default;
        }
    }

    public function has($key) {
        $this->_ensureSessionData();
        return isset($this->_sessionData[$key]);
    }

    public function remove($key) {
        $this->_ensureSessionData();
        unset($this->_sessionData[$key]);

        if($this->manager->isLoggedIn()) {
            $this->manager->getUserModel()->removeClientOptions(
                $this->manager->client->getId(),
                $key
            );
        }

        return $this;
    }

    public function import(array $options) {
        $this->_ensureSessionData();
        $this->_sessionData = array_merge($this->_sessionData, $options);

        if($this->manager->isLoggedIn()) {
            $this->manager->getUserModel()->updateClientOptions(
                $this->manager->client->getId(),
                $options
            );
        }

        return $this;
    }

    public function refresh() {
        $this->_destroySessionData();
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
}