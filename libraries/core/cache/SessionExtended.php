<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache;

use df;
use df\core;
use df\user;

class SessionExtended extends Base implements ISessionExtendedCache {

    const SESSION_LIFE_TIME = null;

    protected $_session;

    protected function __construct() {
        parent::__construct();

        $lifeTime = static::SESSION_LIFE_TIME;

        if(!$lifeTime) {
            $lifeTime = $this->getLifeTime();
        }

        $this->_session = user\Manager::getInstance()->session->getBucket(
                'cache://'.$this->getCacheId()
            )
            ->setLifeTime($lifeTime);
    }

    public function clear() {
        parent::clear();
        $this->clearSessionForAll();

        return $this;
    }

    public function setSession($key, $value) {
        $this->_session->set($key, $value);
        return $this;
    }

    public function getSession($key, $default=null) {
        return $this->_session->get($key, $default);
    }

    public function hasSession($key) {
        return $this->_session->has($key);
    }

    public function removeSession($key) {
        $this->_session->remove($key);
        return $this;
    }

    public function clearSession() {
        $this->_session->clear();
        return $this;
    }

    public function clearSessionForUser($userId) {
        $this->_session->clearForUser($userId);
        return $this;
    }

    public function clearSessionForClient() {
        $this->_session->clearForClient();
        return $this;
    }

    public function clearSessionForAll() {
        $this->_session->clearForAll();
        return $this;
    }
}