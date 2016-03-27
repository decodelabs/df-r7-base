<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\mesh\job;

use df;
use df\core;
use df\mesh;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface ITransactionInitiator {
    public function newTransaction(): ITransaction;
}

interface ITransactionExecutor {
    public function begin();
    public function commit();
    public function rollback();
}

interface ITransaction extends ITransactionExecutor {
    public function isOpen();
    public function registerAdapter(ITransactionAdapter $adapter);
}

interface ITransactionAdapter extends ITransactionExecutor {
    public function getTransactionId();
}

interface ITransactionAware {
    public function setTransaction(ITransaction $transaction=null);
    public function getTransaction(); //: ?ITransaction;
}

interface IQueue {
    public function getTransaction(): ITransaction;
    public function registerAdapter(ITransactionAdapter $adapter);

    public function asap(...$args): IJob;
    public function after(IJob $job, ...$args): IJob;
    public function emitEventAfter(IJob $job, $entity, $action, array $data=null): IJob;
/*
    public function addJob(IJob $job);

    public function ignoreObject($object);
    public function isObjectIgnored($object): bool;
*/
    public function execute();
}

interface IJob {
    public function getId(): string;
    public function getAdapter(); //: ?ITransactionAdapter;

/*
    public function after(IJob $job, IResolution $resolution=null);
    public function addDependency(IDependency $dependency);
*/
    public function addDependency($dependency); // DELETE ME
    public function countDependencies(): int;
    public function hasDependencies(): bool;
    public function untangleDependencies(IQueue $queue);
    public function resolveDependenciesOn(IJob $job);

    public function addSubordinate(IJob $job);
    public function countSubordinates(): int;
    public function hasSubordinates(): bool;
    public function resolveSubordinates();

    public function execute();
}

interface IJobAdapter extends ITransactionAdapter {
    public function getJobAdapterId();
}


trait TAdapterAwareJob {

    protected $_adapter;

    public function getAdapter() {
        return $this->_adapter;
    }
}


interface IDependency {
/*
    public function getRequiredJob(): IJob;
    public function getRequiredJobId(): string;
    public function setResolution(IResolution $resolution=null);
    public function getResolution(); //: ?IResolution
    public function untangle(IQueue $queue, IJob $subordinate);
    public function resolve(IJob $subordinate);
*/
}

interface IResolution {
    public function untangle(IQueue $queue, IJob $subordinate, IJob $dependency);
    public function resolve(IJob $subordinate, IJob $dependency);
}