<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\cache;

use df;
use df\core;
use df\axis;
use df\mesh;

    
trait TBroadcastEvents {

    public function set($key, $value, $lifeTime=null) {
        $this->context->mesh->triggerEvent(new mesh\event\Event(
            'set', ['value' => $value, 'lifeTime' => $lifeTime], $this
        ));

        return parent::set($key, $value, $lifeTime);
    }
    
    public function remove($key) {
        $this->context->mesh->triggerEvent(new mesh\event\Event(
            'remove', [$key], $this
        ));

        return parent::remove($key);
    }

    public function clear() {
        $this->context->mesh->triggerEvent(new mesh\event\Event(
            'clear', null, $this
        ));

        return parent::clear();
    }
    
    public function clearBegins($key) {
        $this->context->mesh->triggerEvent(new mesh\event\Event(
            'clearBegins', ['key' => $key], $this
        ));

        return parent::clearBegins($key);
    }

    public function clearMatches($regex) {
        $this->context->mesh->triggerEvent(new mesh\event\Event(
            'clearMatches', ['regex' => $regex], $this
        ));

        return parent::clearMatches($regex);
    }
}


trait TBroadcastEvents_Session {

    public function setSession($key, $value) {
        $this->context->mesh->triggerEvent(new mesh\event\Event(
            'setSession', ['key' => $key, 'value' => $value], $this
        ));

        return parent::setSession($key, $value);
    }

    public function removeSession($key) {
        $this->context->mesh->triggerEvent(new mesh\event\Event(
            'removeSession', ['key' => $key], $this
        ));

        return parent::removeSession($key);
    }

    public function clearSession() {
        $this->context->mesh->triggerEvent(new mesh\event\Event(
            'clearSession', null, $this
        ));

        return parent::clearSession();
    }
}