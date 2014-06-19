<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\session\perpetuator;

use df;
use df\core;
use df\user;
use df\halo;
    
class Shell implements user\session\IPerpetuator {

    protected $_userKey;
    protected $_inputId;
    protected $_lifeTime;

    public function __construct(user\session\IController $controller) {
        $process = halo\process\Base::getCurrent();

        $uid = $process->getOwnerId();
        $name = $process->getOwnerName();

        $this->_userKey = md5($uid.$name);
        $cache = Shell_Cache::getInstance();

        $this->_inputId = $cache->get($this->_userKey);
    }

    public function setLifeTime($lifeTime) {
        $this->_lifeTime = $lifeTime;
        return $this;
    }

    public function getLifeTime() {
        return $this->_lifeTime;
    }
    
    public function getInputId() {
        return $this->_inputId;
    }

    public function canRecallIdentity() {
        return true;
    }

    public function perpetuate(user\session\IController $controller, user\session\IDescriptor $descriptor) {
        $cache = Shell_Cache::getInstance();
        $cache->set($this->_userKey, $descriptor->getExternalId(), $this->_lifeTime);

        return $this;
    }

    public function destroy(user\session\IController $controller) {
        $cache = Shell_Cache::getInstance();
        $cache->remove($this->_userKey);

        $this->destroyRememberKey($controller);

        return $this;
    }

    public function perpetuateRememberKey(user\session\IController $controller, user\RememberKey $key) {
        // How's this going to work?
        return $this;
    }

    public function getRememberKey(user\session\IController $controller) {
        return null;
    }

    public function destroyRememberKey(user\session\IController $controller) {
        // Derp
        return $this;
    }
}



class Shell_Cache extends core\cache\Base {}