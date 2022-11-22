<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\mesh\job;

use DecodeLabs\Exceptional;

use df\mesh;

class Queue implements IQueue
{
    protected $_jobs = [];
    protected $_ignore = [];
    protected $_transaction;
    protected $_isExecuting = false;
    protected $_store = [];

    public function __construct()
    {
        $this->_transaction = new Transaction();
    }


    // Transaction
    public function getTransaction(): ITransaction
    {
        return $this->_transaction;
    }

    public function registerAdapter(ITransactionAdapter $adapter)
    {
        $this->_transaction->registerAdapter($adapter);
        return $this;
    }


    // Jobs
    public function asap(...$args): IJob
    {
        $id = uniqid();
        $adapter = $callback = $job = null;

        foreach ($args as $arg) {
            if ($arg instanceof IJob) {
                $job = $arg;
                break;
            } elseif (is_string($arg)) {
                $id = $arg;
            } elseif ($arg instanceof IJobAdapter) {
                $adapter = $arg;
            } elseif (is_callable($arg)) {
                $callback = $arg;
            }
        }

        if (!$job) {
            if ($callback === null) {
                throw Exceptional::InvalidArgument(
                    'Generic jobs must have a callback'
                );
            }

            if ($adapter === null && $callback instanceof ITransactionAdapterProvider) {
                $adapter = $callback->getTransactionAdapter();
            }

            $job = new Generic($id, $callback, $adapter);
        }

        $this->addJob($job);
        return $job;
    }

    public function after(IJob $job = null, ...$args): IJob
    {
        if (!$job) {
            return $this->asap(...$args);
        }

        $resolution = null;

        foreach ($args as $i => $arg) {
            if ($arg instanceof IResolution) {
                $resolution = $arg;
                unset($args[$i]);
                break;
            }
        }

        return $this->asap(...$args)->addDependency($job, $resolution);
    }

    public function emitEvent($entity, $action, array $data = null, IJob $activeJob = null)
    {
        mesh\Manager::getInstance()->emitEvent($entity, $action, $data, $this, $activeJob);
    }

    public function emitEventAfter(IJob $job = null, $entity, $action, array $data = null): ?IJob
    {
        if (!$job) {
            $this->emitEvent($entity, $action, $data);
            return null;
        }

        return $this->after($job, function () use ($entity, $action, $data, $job) {
            mesh\Manager::getInstance()->emitEvent($entity, $action, $data, $this, $job);
        });
    }



    public function __call($method, $args)
    {
        if (substr($method, -5) == 'After') {
            $job = array_shift($args);

            if (!$job instanceof IJob
            && $job !== null) {
                throw Exceptional::Runtime(
                    'Cannot prepare dependency, context is not an IJob'
                );
            }

            if ($job) {
                $provider = array_shift($args);

                if (!$provider instanceof IJobProvider) {
                    throw Exceptional::Runtime(
                        'Cannot prepare job, context is not an IJobProvider'
                    );
                }

                return $this->prepareAfter($job, $provider, substr($method, 0, -5), ...$args);
            }
        }

        if (substr($method, -4) == 'Asap') {
            $method = substr($method, 0, -4);
        }

        $provider = array_shift($args);

        if (!$provider instanceof IJobProvider) {
            throw Exceptional::Runtime(
                'Cannot prepare job, context is not an IJobProvider'
            );
        }

        return $this->prepareAsap($provider, $method, ...$args);
    }

    public function prepareAsap(IJobProvider $provider, string $name, ...$args)
    {
        return $this->asap($provider->prepareJob($name, ...$args));
    }

    public function prepareAfter(IJob $job = null, IJobProvider $provider, string $name, ...$args)
    {
        if (!$job) {
            return $this->prepareAsap($provider, $name, ...$args);
        }

        return $this->after($job, $provider->prepareJob($name, ...$args));
    }



    public function addJob(IJob $job)
    {
        $id = $job->getId();

        if ($adapter = $job->getAdapter()) {
            $this->_transaction->registerAdapter($adapter);
        }

        $this->_jobs[] = $job;

        if ($this->_isExecuting && $job instanceof IEventBroadcastingJob) {
            $job->reportPreEvent($this);
        }

        return $this;
    }

    public function hasJob($id): bool
    {
        if ($id instanceof IJob) {
            $id = $id->getId();
        }

        foreach ($this->_jobs as $job) {
            if ($job->getId() == $id) {
                return true;
            }
        }

        return false;
    }

    public function hasJobUsing($object): bool
    {
        $id = $this->getObjectId($object);

        foreach ($this->_jobs as $job) {
            if ($job->getObjectId() == $id) {
                return true;
            }
        }

        return false;
    }

    public function getJob($id)
    {
        if ($id instanceof IJob) {
            $id = $id->getId();
        }

        foreach ($this->_jobs as $job) {
            if ($job->getId() == $id) {
                return $job;
            }
        }
    }

    public function getJobsUsing($object): array
    {
        $output = [];
        $id = $this->getObjectId($object);

        foreach ($this->_jobs as $job) {
            if ($job->getObjectId() == $id) {
                $output[] = $job;
            }
        }

        return $output;
    }

    public function getLastJobUsing($object)
    {
        $id = $this->getObjectId($object);

        foreach (array_reverse($this->_jobs) as $job) {
            if ($job->getObjectId() == $id) {
                return $job;
            }
        }
    }



    // Objects
    public function ignore($object)
    {
        $id = $this->getObjectId($object);

        if (isset($this->_ignore[$id])) {
            $this->_ignore[$id]++;
        } else {
            $this->_ignore[$id] = 1;
        }

        return $this;
    }

    public function unignore($object)
    {
        $id = $this->getObjectId($object);

        if (isset($this->_ignore[$id]) && $this->_ignore[$id] > 1) {
            $this->_ignore[$id]--;
        } else {
            unset($this->_ignore[$id]);
        }

        return $this;
    }

    public function forget($object)
    {
        $id = $this->getObjectId($object);
        unset($this->_ignore[$id]);
        return $this;
    }

    public function isIgnored($object): bool
    {
        $id = $this->getObjectId($object);
        return isset($this->_ignore[$id]);
    }


    public function isDeployed($object): bool
    {
        if ($object instanceof IJob) {
            return $this->hasJob($object);
        }

        $id = $this->getObjectId($object);

        if (isset($this->_ignore[$id])) {
            return true;
        }

        foreach ($this->_jobs as $job) {
            if ($job->getObjectId() == $id) {
                return true;
            }
        }

        return false;
    }



    public static function getObjectId($object): string
    {
        if ($object instanceof IJob) {
            return $object->getObjectId();
        }

        if (is_scalar($object)) {
            return (string)$object;
            /*
            } else if($object instanceof mesh\entity\ILocatorProvider) {
                return (string)$object->getEntityLocator();
             */
        } else {
            return spl_object_hash($object);
        }
    }



    // Store

    /**
     * @return $this
     */
    public function setStore(
        string $key,
        mixed $value
    ): static {
        $this->_store[$key] = $value;
        return $this;
    }

    public function getStore(string $key): mixed
    {
        return $this->_store[$key] ?? null;
    }

    public function hasStore(string $key): bool
    {
        return isset($this->_store[$key]);
    }

    /**
     * @return $this
     */
    public function removeStore(string $key): static
    {
        unset($this->_store[$key]);
        return $this;
    }

    /**
     * @return $this
     */
    public function clearStore(): static
    {
        $this->_store = [];
        return $this;
    }



    // Runner
    public function execute()
    {
        if ($this->_isExecuting) {
            return $this;
        }

        $this->_isExecuting = true;
        $this->_transaction->begin();

        try {
            $this->_jobs = (array)array_filter($this->_jobs);

            foreach ($this->_jobs as $job) {
                if ($job instanceof IEventBroadcastingJob) {
                    $job->reportPreEvent($this);
                }
            }

            $this->_sortJobs();

            while (!empty($this->_jobs)) {
                foreach ($this->_jobs as $job) {
                    if (!$job->hasDependencies()) {
                        break;
                    }

                    if ($job->untangleDependencies($this)) {
                        $this->_sortJobs();
                        continue 2;
                        //break;
                    }
                }

                $job = array_shift($this->_jobs);

                if (!$job) {
                    continue;
                }

                if ($job->hasDependencies()) {
                    throw Exceptional::Runtime(
                        'Unable to untangle job dependencies'
                    );
                }


                if ($job instanceof IEventBroadcastingJob) {
                    $job->reportExecuteEvent($this);
                }

                $job->execute();

                if ($job instanceof IEventBroadcastingJob) {
                    $job->reportPostEvent($this);
                }

                if ($job->resolveSubordinates()) {
                    $this->_sortJobs();
                }
            }
        } catch (\Throwable $e) {
            $this->_transaction->rollback();
            throw $e;
        }

        $this->_transaction->commit();
        $this->_isExecuting = false;

        $this->clearStore();

        return $this;
    }

    protected function _sortJobs()
    {
        uasort($this->_jobs, function ($jobA, $jobB) {
            return $jobA->getDependencyScore() <=> $jobB->getDependencyScore();
        });
    }
}
