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
    
class Shell implements user\ISessionPerpetuator {

    protected $_userKey;
    protected $_inputId;
    protected $_lifeTime;

    public function __construct(user\IManager $manager) {
        $process = halo\process\Base::getCurrent();

        $uid = $process->getOwnerId();
        $name = $process->getOwnerName();

        $this->_userKey = md5($uid.$name);
        $cache = Shell_Cache::getInstance($manager->getApplication());

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

    public function perpetuate(user\IManager $manager, user\ISessionDescriptor $descriptor) {
        $cache = Shell_Cache::getInstance($manager->getApplication());
        $cache->set($this->_userKey, $descriptor->getExternalId(), $this->_lifeTime);

        return $this;
    }
}



class Shell_Cache extends core\cache\Base {}