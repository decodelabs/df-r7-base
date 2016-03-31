<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh\job;

use df;
use df\core;
use df\mesh;

abstract class Base implements IJob {

    protected $_id;
    protected $_dependencies = [];
    protected $_subordinates = [];

    protected function _setId($id) {
        $adapter = $this->getAdapter();

        if($adapter) {
            $prefix = $adapter->getJobAdapterId();
        } else {
            $prefix = uniqid();
        }

        $this->_id = get_class($this).'|'.$prefix.':'.$id;
    }

    public function getId(): string {
        return $this->_id;
    }

    public function getObjectId(): string {
        return $this->getId();
    }


// Dependencies
    public function addDependency($dependency, IResolution $resolution=null) {
        if($dependency instanceof IJob) {
            $dependency = new Dependency($dependency, $resolution);
        } else if(!$dependency instanceof IDependency) {
            throw new InvalidArgumentException('Invalid dependency');
        }

        $this->_dependencies[] = $dependency;
        $dependency->getRequiredJob()->addSubordinate($this);

        return $this;
    }

    public function countDependencies(): int {
        return count($this->_dependencies);
    }

    public function hasDependencies(): bool {
        return !empty($this->_dependencies);
    }

    public function getDependencyScore(): float {
        $output = 0;

        foreach($this->_dependencies as $dependency) {
            $output += 1;

            if($dependency->getResolution()
            || get_class($dependency) != 'df\\mesh\\job\\Dependency' // DELETE ME!!!!
            ) {
                $output += 0.001;
            }
        }

        return $output;
    }

    public function untangleDependencies(IQueue $queue) {
        while(!empty($this->_dependencies)) {
            $dependency = array_shift($this->_dependencies);
            $dependency->untangle($queue, $this);
        }

        return $this;
    }

    public function resolveDependenciesOn(IJob $job) {
        $jobId = $job->getId();

        foreach($this->_dependencies as $id => $dependency) {
            if($jobId == $dependency->getRequiredJobId()) {
                $dependency->resolve($this);
                unset($this->_dependencies[$id]);
            }
        }

        return $this;
    }


// Subordinates
    public function addSubordinate(IJob $job) {
        $this->_subordinates[$job->getId()] = $job;
        return $this;
    }

    public function countSubordinates(): int {
        return count($this->_subordinates);
    }

    public function hasSubordinates(): bool {
        return !empty($this->_subordinates);
    }

    public function resolveSubordinates() {
        $output = false;

        while(!empty($this->_subordinates)) {
            $output = true;
            $job = array_shift($this->_subordinates);
            $job->resolveDependenciesOn($this);
        }

        return $output;
    }
}