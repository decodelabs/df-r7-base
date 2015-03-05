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

    public function __construct(user\session\IController $controller) {
        $process = halo\process\Base::getCurrent();

        $uid = $process->getOwnerId();
        $name = $process->getOwnerName();

        $this->_userKey = md5($uid.$name);
        $cache = Shell_Cache::getInstance();

        $this->_inputId = $cache->get($this->_userKey);
    }

    public function getInputId() {
        return $this->_inputId;
    }

    public function canRecallIdentity() {
        return true;
    }

    public function perpetuate(user\session\IController $controller, user\session\IDescriptor $descriptor) {
        $cache = Shell_Cache::getInstance();
        $cache->set($this->_userKey, $descriptor->getExternalId());

        return $this;
    }

    public function destroy(user\session\IController $controller) {
        $cache = Shell_Cache::getInstance();
        $cache->remove($this->_userKey);

        $this->destroyRecallKey($controller);

        return $this;
    }

    public function perpetuateRecallKey(user\session\IController $controller, user\session\RecallKey $key) {
        // How's this going to work?
        return $this;
    }

    public function getRecallKey(user\session\IController $controller) {
        return null;
    }

    public function destroyRecallKey(user\session\IController $controller) {
        // Derp
        return $this;
    }
}



class Shell_Cache extends core\cache\Base {}