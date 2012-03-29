<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record\task;

use df;
use df\core;
use df\opal;

// Exceptions
interface IException extends opal\query\record\IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces
interface ITaskSet {
    public function getTransaction();
    public function save(opal\query\record\IRecord $record);
    public function insert(opal\query\record\IRecord $record);
    public function replace(opal\query\record\IRecord $record);
    public function update(opal\query\record\IRecord $record);
    public function delete(opal\query\record\IRecord $record);
    public function execute();
}


interface ITask {
    public function getId();
    public function getAdapter();
    
    public function addDependency(opal\query\record\task\dependency\IDependency $dependency);
    public function countDependencies();
    public function resolveDependencies(ITaskSet $taskSet);
    public function applyDependencyResolution(ITask $dependencyTask);
    public function applyResolutionToDependants();
    
    public function execute(opal\query\ITransaction $transaction);
}


interface IInsertTask extends ITask {}
interface IReplaceTask extends ITask {}
interface IUpdateTask extends ITask {}
interface IDeleteTask extends ITask {}



interface IKeyTask extends ITask {
    public function getKeys();
}

interface IDeleteKeyTask extends IDeleteTask, IKeyTask {}


interface IRecordTask extends ITask {
    public function getRecord();
}

interface IInsertRecordTask extends IRecordTask, IInsertTask {}
interface IReplaceRecordTask extends IRecordTask, IReplaceTask {}
interface IUpdateRecordTask extends IRecordTask, IUpdateTask {}
interface IDeleteRecordTask extends IRecordTask, IDeleteTask {}
