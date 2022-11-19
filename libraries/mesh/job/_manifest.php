<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\mesh\job;

use df\mesh;

interface ITransactionInitiator
{
    public function newTransaction(): ITransaction;
}

interface ITransactionExecutor
{
    public function begin();
    public function commit();
    public function rollback();
}

interface ITransaction extends ITransactionExecutor
{
    public function isOpen();
    public function registerAdapter(ITransactionAdapter $adapter);
}

interface ITransactionAdapter extends ITransactionExecutor
{
    public function getTransactionId();
}

interface ITransactionAware
{
    public function setTransaction(ITransaction $transaction = null);
    public function getTransaction(): ?ITransaction;
}

interface ITransactionAdapterProvider
{
    public function getTransactionAdapter();
}

interface IQueue
{
    public function getTransaction(): ITransaction;
    public function registerAdapter(ITransactionAdapter $adapter);

    public function asap(...$args): IJob;
    public function after(IJob $job = null, ...$args): IJob;

    public function emitEvent($entity, $action, array $data = null);
    public function emitEventAfter(IJob $job = null, $entity, $action, array $data = null): ?IJob;

    public function __call($method, $args);
    public function prepareAsap(IJobProvider $provider, string $name, ...$args);
    public function prepareAfter(IJob $job = null, IJobProvider $provider, string $name, ...$args);

    public function addJob(IJob $job);
    public function hasJob($id): bool;
    public function hasJobUsing($object): bool;
    public function getJob($id);
    public function getJobsUsing($object): array;
    public function getLastJobUsing($object);

    public function ignore($object);
    public function unignore($object);
    public function forget($object);
    public function isIgnored($object): bool;

    public function isDeployed($object): bool;

    public static function getObjectId($object);


    /**
     * @return $this
     */
    public function setStore(
        string $key,
        mixed $value
    ): static;

    public function getStore(string $key): mixed;
    public function hasStore(string $key): bool;

    /**
     * @return $this
     */
    public function removeStore(string $key): static;

    /**
     * @return $this
     */
    public function clearStore(): static;

    public function execute();
}

interface IJobProvider
{
    public function prepareJob(string $name, ...$args): IJob;
}


interface IJob
{
    public function getId(): string;
    public function getObjectId(): string;
    public function getAdapter(): ?ITransactionAdapter;

    public function addDependency($dependency, IResolution $resolution = null);
    public function countDependencies(): int;
    public function hasDependencies(): bool;
    public function getDependencyScore(): float;
    public function untangleDependencies(IQueue $queue): bool;
    public function resolveDependenciesOn(IJob $job);

    public function addSubordinate(IJob $job);
    public function countSubordinates(): int;
    public function hasSubordinates(): bool;
    public function resolveSubordinates();

    public function execute();
}

interface IEventBroadcastingJob extends mesh\job\IJob
{
    public function reportPreEvent(IQueue $queue);
    public function reportExecuteEvent(IQueue $queue);
    public function reportPostEvent(IQueue $queue);
}

interface IJobAdapter extends ITransactionAdapter
{
    public function getJobAdapterId();
}


trait TAdapterAwareJob
{
    protected $_adapter;

    public function getAdapter(): ?ITransactionAdapter
    {
        return $this->_adapter;
    }
}


interface IDependency
{
    public function getRequiredJob(): IJob;
    public function getRequiredJobId(): string;

    public function setResolution(IResolution $resolution = null);
    public function getResolution(): ?IResolution;

    public function untangle(IQueue $queue, IJob $subordinate): bool;
    public function resolve(IJob $subordinate);
}

interface IResolution
{
    public function untangle(IQueue $queue, IJob $subordinate, IJob $dependency): bool;
    public function resolve(IJob $subordinate, IJob $dependency);
}
