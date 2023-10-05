<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh\event;

use df\core;
use df\mesh;

class Event implements IEvent
{
    use core\collection\TArrayCollection_Map;

    protected $_action;
    protected $_entityLocator;
    protected $_entity;
    protected $_jobQueue;
    protected $_job;

    public function __construct($entity, $action, array $data = null, mesh\job\IQueue $jobQueue = null, mesh\job\IJob $job = null)
    {
        if (!$entity instanceof mesh\entity\ILocatorProvider) {
            $entity = mesh\entity\Locator::factory($entity);
        }

        $this->setEntity($entity);
        $this->setAction($action);
        $this->setJobQueue($jobQueue);
        $this->setJob($job);

        if ($data !== null) {
            $this->import($data);
        }
    }


    // Entity
    public function setEntity(mesh\entity\ILocatorProvider $entity)
    {
        if ($entity instanceof mesh\entity\IEntity) {
            $this->_entity = $entity;
        }

        $this->_entityLocator = $entity->getEntityLocator();
        return $this;
    }

    public function hasEntity()
    {
        return $this->_entity !== null;
    }

    public function getEntity()
    {
        if (!$this->_entity) {
            if (!$this->_entityLocator) {
                return null;
            }

            $this->_entity = mesh\Manager::getInstance()->fetchEntity($this->_entityLocator);
        }

        return $this->_entity;
    }

    public function getEntityLocator()
    {
        return $this->_entityLocator;
    }

    public function hasCachedEntity()
    {
        return $this->_entity !== null;
    }

    public function getCachedEntity()
    {
        return $this->_entity;
    }

    public function clearCachedEntity()
    {
        $this->_entity = null;
        return $this;
    }


    // Action
    public function setAction($action)
    {
        $this->_action = lcfirst($action);
        return $this;
    }

    public function getAction()
    {
        return $this->_action;
    }


    // Job
    public function setJobQueue(mesh\job\IQueue $queue = null)
    {
        $this->_jobQueue = $queue;
        return $this;
    }

    public function getJobQueue(): ?mesh\job\IQueue
    {
        return $this->_jobQueue;
    }

    public function setJob(mesh\job\IJob $job = null)
    {
        $this->_job = $job;
        return $this;
    }

    public function getJob(): ?mesh\job\IJob
    {
        return $this->_job;
    }
}
