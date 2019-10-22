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

abstract class Base implements halo\event\IBinding
{
    public $id;
    public $isPersistent = true;
    public $isFrozen = false;
    public $handler;
    public $eventResource;
    public $dispatcher;

    public function __construct(halo\event\IDispatcher $dispatcher, string $id, $isPersistent, $callback)
    {
        $this->id = $id;
        $this->isPersistent = (bool)$isPersistent;
        $this->handler = core\lang\Callback::factory($callback);
        $this->dispatcher = $dispatcher;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function isPersistent()
    {
        return $this->isPersistent;
    }

    public function getHandler()
    {
        return $this->handler;
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    public function setEventResource($resource)
    {
        $this->eventResource = $resource;
        return $this;
    }

    public function getEventResource()
    {
        return $this->eventResource;
    }

    public function freeze()
    {
        $this->dispatcher->freezeBinding($this);
        return $this;
    }

    public function unfreeze()
    {
        $this->dispatcher->unfreezeBinding($this);
        return $this;
    }

    public function isFrozen()
    {
        return $this->isFrozen;
    }

    public function markFrozen(bool $frozen): halo\event\IBinding
    {
        $this->isFrozen = $frozen;
        return $this;
    }
}
