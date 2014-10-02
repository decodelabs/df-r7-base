<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\binding;

use df;
use df\core;
use df\halo;
use df\link;
use df\mesh;

abstract class Base implements halo\event\IBinding {

    public $id;
    public $isPersistent = true;
    public $isFrozen = false;
    public $handler;
    public $eventResource;
    public $dispatcher;

    public function __construct(halo\event\IDispatcher $dispatcher, $id, $isPersistent, $callback) {
        $this->id = $id;
        $this->isPersistent = (bool)$isPersistent;
        $this->handler = mesh\Callback::factory($callback);
        $this->dispatcher = $dispatcher;
    }

    public function getId() {
        return $this->id;
    }

    public function isPersistent() {
        return $this->isPersistent;
    }

    public function getHandler() {
        return $this->handler;
    }

    public function getDispatcher() {
        return $this->dispatcher;
    }

    public function setEventResource($resource) {
        $this->eventResource = $resource;
        return $this;
    }

    public function getEventResource() {
        return $this->eventResource;
    }

    public function freeze() {
        $this->dispatcher->freezeBinding($this);
        return $this;
    }

    public function unfreeze() {
        $this->dispatcher->unfreezeBinding($this);
        return $this;
    }

    public function isFrozen() {
        return $this->isFrozen;
    }
}