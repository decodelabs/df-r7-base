<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\cache;

use df;
use df\core;
use df\axis;

    
trait TBroadcastEvents {

    public function set($key, $value, $lifeTime=null) {
        $this->context->policy->triggerEvent(new core\policy\Event(
            'set', ['value' => $value, 'lifeTime' => $lifeTime], $this
        ));

        return parent::set($key, $value, $lifeTime);
    }
    
    public function remove($key) {
        $this->context->policy->triggerEvent(new core\policy\Event(
            'remove', [$key], $this
        ));

        return parent::remove($key);
    }

    public function clear() {
        $this->context->policy->triggerEvent(new core\policy\Event(
            'clear', null, $this
        ));

        return parent::clear();
    }
    
    public function clearBegins($key) {
        $this->context->policy->triggerEvent(new core\policy\Event(
            'clearBegins', ['key' => $key], $this
        ));

        return parent::clearBegins($key);
    }

    public function clearMatches($regex) {
        $this->context->policy->triggerEvent(new core\policy\Event(
            'clearMatches', ['regex' => $regex], $this
        ));

        return parent::clearMatches($regex);
    }
}


trait TBroadcastEvents_Session {

    public function setSession($key, $value) {
        $this->context->policy->triggerEvent(new core\policy\Event(
            'setSession', ['key' => $key, 'value' => $value], $this
        ));

        return parent::setSession($key, $value);
    }

    public function removeSession($key) {
        $this->context->policy->triggerEvent(new core\policy\Event(
            'removeSession', ['key' => $key], $this
        ));

        return parent::removeSession($key);
    }

    public function clearSession() {
        $this->context->policy->triggerEvent(new core\policy\Event(
            'clearSession', null, $this
        ));

        return parent::clearSession();
    }
}