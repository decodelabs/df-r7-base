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

    protected $_session;

    protected function __construct(core\IApplication $application) {
        parent::__construct($application);

        $this->_session = user\Manager::getInstance($application)->getSessionNamespace(
                'cache://'.$this->getCacheId()
            )
            ->setLifeTime($this->getLifeTime());
    }

    public function clear() {
        parent::clear();
        $this->clearSession();

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
}