<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\user\session\perpetuator;

use DecodeLabs\Systemic;
use df\core;

use df\user;

class Shell implements user\session\IPerpetuator
{
    protected $_userKey;
    protected $_inputId;

    public function __construct()
    {
        $process = Systemic::getCurrentProcess();

        $uid = $process->getOwnerId();
        $name = $process->getOwnerName();

        $this->_userKey = md5($uid . $name);
        $cache = Shell_Cache::getInstance();

        $this->_inputId = $cache->get($this->_userKey);
    }

    public function getInputId()
    {
        return $this->_inputId;
    }

    public function canRecallIdentity()
    {
        return true;
    }

    public function perpetuate(user\session\IController $controller, user\session\Descriptor $descriptor)
    {
        $cache = Shell_Cache::getInstance();
        $cache->set($this->_userKey, $descriptor->getPublicKey());

        return $this;
    }

    public function destroy(user\session\IController $controller)
    {
        $cache = Shell_Cache::getInstance();
        $cache->remove($this->_userKey);

        $this->destroyRecallKey($controller);

        return $this;
    }

    public function handleDeadPublicKey($publicKey)
    {
    }

    public function perpetuateRecallKey(user\session\IController $controller, user\session\RecallKey $key)
    {
        // How's this going to work?
        return $this;
    }

    public function getRecallKey(user\session\IController $controller)
    {
        return null;
    }

    public function destroyRecallKey(user\session\IController $controller)
    {
        // Derp
        return $this;
    }
}



class Shell_Cache extends core\cache\Base
{
}
