<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh\job;

class Dependency implements IDependency
{
    protected $_requiredJob;
    protected $_resolution;

    public function __construct(IJob $requiredJob, IResolution $resolution = null)
    {
        $this->_requiredJob = $requiredJob;
        $this->_resolution = $resolution;
    }

    public function getRequiredJob(): IJob
    {
        return $this->_requiredJob;
    }

    public function getRequiredJobId(): string
    {
        return $this->_requiredJob->getId();
    }


    public function setResolution(IResolution $resolution = null)
    {
        $this->_resolution = $resolution;
        return $this;
    }

    public function getResolution(): ?IResolution
    {
        return $this->_resolution;
    }


    public function untangle(IQueue $queue, IJob $subordinate): bool
    {
        if ($this->_resolution) {
            return $this->_resolution->untangle($queue, $subordinate, $this->_requiredJob);
        }

        return true;
    }

    public function resolve(IJob $subordinate)
    {
        if ($this->_resolution) {
            $this->_resolution->resolve($subordinate, $this->_requiredJob);
        }

        return $this;
    }
}
