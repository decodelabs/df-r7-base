<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\mesh\event;

use df\core;
use df\mesh;

interface IEmitter
{
    public function emitEvent($entity, $action, array $data = null, mesh\job\IQueue $jobQueue = null, mesh\job\IJob $job = null);
    public function emitEventObject(IEvent $event);
}

trait TEmitter
{
    public function emitEvent($entity, $action, array $data = null, mesh\job\IQueue $jobQueue = null, mesh\job\IJob $job = null)
    {
        if ($entity === null) {
            return $this;
        }

        return $this->emitEventObject(new mesh\event\Event($entity, $action, $data, $jobQueue, $job));
    }
}


interface IEvent extends core\collection\IMap
{
    // Entity
    public function setEntity(mesh\entity\ILocatorProvider $entity);
    public function hasEntity();
    public function getEntity();
    public function hasCachedEntity();
    public function getCachedEntity();
    public function clearCachedEntity();
    public function getEntityLocator();

    // Action
    public function setAction($action);
    public function getAction();

    // Job
    public function setJobQueue(mesh\job\IQueue $queue = null);
    public function getJobQueue(): ?mesh\job\IQueue;
    public function setJob(mesh\job\IJob $job = null);
    public function getJob(): ?mesh\job\IJob;
}

interface IListener
{
    public function handleEvent(IEvent $event);
}


interface IHook extends core\IContextAware
{
    public static function triggerEvent(IEvent $event);
    public function getName(): string;
    public function getEventMap();
}
