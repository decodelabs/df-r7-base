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
        $this->context->mesh->emitEvent($this, 'set', [
            'value' => $value,
            'lifeTime' => $lifeTime
        ]);

        return parent::set($key, $value, $lifeTime);
    }

    public function remove(...$keys) {
        $this->context->mesh->emitEvent($this, 'remove', [
            'keys' => $keys
        ]);

        return parent::remove(...$keys);
    }

    public function clear() {
        $this->context->mesh->emitEvent($this, 'clear');

        return parent::clear();
    }

    public function clearBegins($key) {
        $this->context->mesh->emitEvent($this, 'clearBegins', [
            'key' => $key
        ]);

        return parent::clearBegins($key);
    }

    public function clearMatches($regex) {
        $this->context->mesh->emitEvent($this, 'clearMatches', [
            'regex' => $regex
        ]);

        return parent::clearMatches($regex);
    }
}


trait TBroadcastEvents_Session {

    public function setSession($key, $value) {
        $this->context->mesh->emitEvent($this, 'setSession', [
            'key' => $key,
            'value' => $value
        ]);

        return parent::setSession($key, $value);
    }

    public function removeSession($key) {
        $this->context->mesh->emitEvent($this, 'removeSession', [
            'key' => $key
        ]);

        return parent::removeSession($key);
    }

    public function clearSession() {
        $this->context->mesh->emitEvent($this, 'clearSession');

        return parent::clearSession();
    }
}