<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\record;

use df;
use df\core;
use df\opal;

// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class ValuePreparationException extends RuntimeException {}


// Interfaces
interface IRecord extends core\collection\IMappedCollection {
    public function getRecordAdapter();
    
    public function isNew();
    public function makeNew();
    
    public function getPrimaryManifest();
    
    public function hasChanged($field=null);
    public function clearChanges();
    public function getChanges();
    public function getChangesForStorage();
    public function getValuesForStorage();
    public function getUpdatedValues();
    public function getUpdatedValuesForStorage();
    public function getAddedValues();
    public function getAddedValuesForStorage();
    public function getOriginalValues();
    public function getOriginalValuesForStorage();
    public function acceptChanges($insertId=null, array $insertData=null);
    public function markAsChanged($field);
    
    public function populateWithPreparedData(array $row);
    public function populateWithRawData($row);
    
    public function save();
    public function delete();
    public function deploySaveTasks(opal\query\record\task\ITaskSet $taskSet);
    public function deployDeleteTasks(opal\query\record\task\ITaskSet $taskSet);
}


interface IValueContainer extends core\IValueContainer {
    public function getValueForStorage();
    public function duplicateForChangeList();
    public function eq($value);
    public function getDumpValue();
}

interface IPreparedValueContainer extends IValueContainer {
    public function isPrepared();
    public function prepareValue(opal\query\record\IRecord $record, $fieldName);
    public function prepareToSetValue(opal\query\record\IRecord $record, $fieldName);
}

interface ITaskAwareValueContainer extends IValueContainer {
    public function deploySaveTasks(opal\query\record\task\ITaskSet $taskSet, IRecord $record, $fieldName, opal\query\record\task\ITask $task=null);
    public function acceptSaveTaskChanges(opal\query\record\IRecord $record);
    public function deployDeleteTasks(opal\query\record\task\ITaskSet $taskSet, IRecord $record, $fieldName, opal\query\record\task\ITask $task=null);
    public function acceptDeleteTaskChanges(opal\query\record\IRecord $record);
}


interface IManyRelationValueContainer extends IValueContainer {
    public function add($record);
    public function addList(array $records);
    public function remove($record);
    public function removeList(array $records);
    public function removeAll();
        
    public function select($field1=null);
    public function fetch();
}


interface IPrimaryManifest {
    public function toArray();
    public function updateWith($record);
    public function countFields();
    public function getFieldNames();
    public function isNull();
    public function getCombinedId();
    public function getFirstKeyValue();
    public function duplicateWith($values);
    public function eq(IPrimaryManifest $manifest);
}
