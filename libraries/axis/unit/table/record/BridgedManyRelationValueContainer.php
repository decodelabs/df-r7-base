<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table\record;

use df;
use df\core;
use df\axis;
use df\opal;

class BridgedManyRelationValueContainer implements 
    opal\record\ITaskAwareValueContainer, 
    opal\record\IPreparedValueContainer,
    opal\record\IManyRelationValueContainer,
    core\IArrayProvider,
    \Countable,
    \IteratorAggregate {
    
    protected $_current = array();
    protected $_new = array();
    protected $_remove = array();
    protected $_removeAll = false;
    
    protected $_localPrimaryKeySet;
    protected $_targetPrimaryKeySet;
    protected $_field;

    protected $_record = null;
    
    public function __construct(axis\schema\IBridgedRelationField $field) {
        $this->_field = $field;
        $this->_localPrimaryKeySet = $field->getLocalRelationManifest()->toPrimaryKeySet();
        $this->_targetPrimaryKeySet = $field->getTargetRelationManifest()->toPrimaryKeySet();
    }
    
    public function isPrepared() {
        return $this->_record !== null;
    }
    
    public function prepareValue(opal\record\IRecord $record, $fieldName) {
        $this->_record = $record;
        $this->_localPrimaryKeySet->updateWith($record);

        return $this;
    }
    
    public function prepareToSetValue(opal\record\IRecord $record, $fieldName) {
        return $this->prepareValue($record, $fieldName);
    }
    
    public function setValue($value) {
        $this->removeAll();
        
        if(!is_array($value)) {
            $value = array($value);
        }

        foreach($value as $id) {
            $this->add($id);
        }
        
        return $this;
    }
    
    public function getValue($default=null) {
        return $this;
    }
    
    public function getValueForStorage() {
        return null;
    }
    
    public function eq($value) {
        return false;
    }
    
    public function duplicateForChangeList() {
        return $this;
    }

    public function getNew() {
        return $this->_new;
    }

    public function getCurrent() {
        return $this->_current;
    }

    public function getRemoved() {
        return $this->_remove;
    }
    
    
// Collection
    public function toArray() {
        return array_merge($this->_current, $this->_new);
    }
    
    public function count() {
        return count($this->toArray());
    }

    public function getIterator() {
        return new \ArrayIterator($this->toArray());
    }
    
    public function getPopulated() {
        return $this->_current;
    }

    
// Key sets
    public function getLocalPrimaryKeySet() {
        return $this->_localPrimaryKeySet;
    }
    
    public function getTargetPrimaryKeySet() {
        return $this->_targetPrimaryKeySet;
    }
    
    
// Records
    public function add($record) {
        return $this->addList(func_get_args());
    }
    
    public function addList(array $records) {
        $index = $this->_normalizeInputRecordList($records);
        
        foreach($index as $id => $record) {
            $this->_new[$id] = $record;
        }
        
        if($this->_record) {
            $this->_record->markAsChanged($this->_field->getName());
        }
        
        return $this;
    }
    
    public function populate($record) {
        return $this->populateList(func_get_args());
    }

    public function populateList(array $records) {
        foreach($this->_normalizeInputRecordList($records) as $id => $record) {
            $this->_current[$id] = $record;
        }

        return $this;
    }
    
    public function remove($record) {
        return $this->removeList(func_get_args());
    }
    
    public function removeList(array $records) {
        foreach($records as $record) {
            if($record instanceof opal\record\IRecord) {
                $id = opal\record\Base::extractRecordId($record);
            } else if($record instanceof opal\record\IPrimaryKeySet) {
                $id = opal\record\Base::extractRecordId($record);
            } else {
                $record = $this->_targetPrimaryKeySet->duplicateWith($record);
                $id = opal\record\Base::extractRecordId($record);
            }
            
            if(isset($this->_new[$id])) {
                unset($this->_new[$id]);
            } else if(isset($this->_current[$id])) {
                $this->_remove[$id] = $this->_current[$id];
                unset($this->_current[$id]);
            } else {
                $this->_remove[$id] = $record;
            }
        }
        
        if($this->_record) {
            $this->_record->markAsChanged($this->_field->getName());
        }
        
        return $this;
    }
    
    public function removeAll() {
        $this->_new = array();
        $this->_current = array();
        $this->_removeAll = true;
        return $this;
    }


    protected function _normalizeInputRecordList(array $records) {
        $index = array();
        $lookupKeySets = array();
        
        foreach($records as $record) {
            if($record instanceof opal\record\IRecord) {
                $id = opal\record\Base::extractRecordId($record);
            } else if($record instanceof opal\record\IPrimaryKeySet) {
                $id = opal\record\Base::extractRecordId($record);
                $lookupKeySets[$id] = $record;
            } else {
                $record = $this->_targetPrimaryKeySet->duplicateWith($record);
                $id = opal\record\Base::extractRecordId($record);
                $lookupKeySets[$id] = $record;
            }
            
            if(!isset($this->_current[$id])) {
                $index[(string)$id] = $record;
            }
        }
        
        if(!empty($lookupKeySets)) {
            $application = $this->_record->getRecordAdapter()->getApplication();
            $targetUnit = $this->_getTargetUnit($application);

            $query = opal\query\Initiator::factory($application)
                ->beginSelect($this->_targetPrimaryKeySet->getFieldNames())
                ->from($targetUnit);
            
            foreach($lookupKeySets as $keySet) {
                $clause = $query->beginOrWhereClause();
                
                foreach($keySet->toArray() as $key => $value) {
                    $clause->where($key, '=', $value);
                }
                
                $clause->endClause();
            }
            
            $res = $query->toArray();
            
            foreach($lookupKeySets as $id => $keySet) {
                if(empty($res)) {
                    unset($index[$id]);
                    continue;
                }
                
                $keys = $keySet->toArray();
                $found = false;
                
                foreach($res as $row) {
                    foreach($keys as $key => $value) {
                        if(!isset($row[$key]) || $row[$key] != $value) {
                            continue 2;
                        }
                    }
                    
                    $found = true;
                    break;
                }
                
                if(!$found) {
                    unset($index[$id]);
                }
            }
        }

        return $index;
    }
    

    public function getBridgeUnit() {
        $application = null;

        if($this->_record) {
            $application = $this->_record->getRecordAdapter()->getApplication();
        }
        
        return $this->_getBridgeUnit($application);
    }

    protected function _getBridgeUnit(core\IApplication $application=null) {
        return axis\Model::loadUnitFromId($this->_field->getBridgeUnitId(), $application);
    }

    public function getTargetUnit() {
        $application = null;

        if($this->_record) {
            $application = $this->_record->getRecordAdapter()->getApplication();
        }
        
        return $this->_getTargetUnit($application);
    }

    protected function _getTargetUnit(core\IApplication $application=null) {
        return axis\Model::loadUnitFromId($this->_field->getTargetUnitId(), $application);
    }

    public function getBridgeLocalFieldName() {
        return $this->_field->getBridgeLocalFieldName();
    }

    public function getBridgeTargetFieldName() {
        return $this->_field->getBridgeTargetFieldName();
    }

    public function newBridgeRecord(array $values=null) {
        return $this->getBridgeUnit()->newRecord($values);
    }

    
// Query
    public function select($field1=null) {
        if(!$this->_record) {
            throw new opal\record\ValuePreparationException(
                'Cannot lookup relations, value container has not been prepared'
            );
        }
        
        $this->_localPrimaryKeySet->updateWith($this->_record);

        $localUnit = $this->_record->getRecordAdapter();
        $application = $localUnit->getApplication();
        
        $targetUnit = $this->_getTargetUnit($application);
        $bridgeUnit = $this->_getBridgeUnit($application);

        $localFieldName = $this->_field->getName();
        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();
        $bridgeTargetFieldName = $this->_field->getBridgeTargetFieldName();
        $bridgeAlias = $bridgeUnit->getUnitName();

        if(false !== strpos($bridgeAlias, '(')) {
            $bridgeAlias = $bridgeLocalFieldName.'Bridge';
        }
        
        return opal\query\Initiator::factory($application)
            ->beginSelect(func_get_args())
            ->from($targetUnit, $localFieldName)
        
            // Join bridge table as constraint
            ->join($bridgeUnit->getBridgeFieldNames($localFieldName, [$bridgeTargetFieldName]))
                ->from($bridgeUnit, $bridgeAlias)
                ->on($bridgeAlias.'.'.$bridgeTargetFieldName, '=', $localFieldName.'.@primary')
                ->endJoin()

            // Add local primary key(s) as prerequisite
            ->wherePrerequisite($bridgeAlias.'.'.$bridgeLocalFieldName, '=', $this->_localPrimaryKeySet);
    }

    public function selectDistinct($field1=null) {
        $query = call_user_func_array([$this, 'select'], func_get_args());
        $query->isDistinct(true);
        return $query;
    }

    public function selectFromBridge($field1=null) {
        if(!$this->_record) {
            throw new opal\record\ValuePreparationException(
                'Cannot lookup relations, value container has not been prepared'
            );
        }
        
        $this->_localPrimaryKeySet->updateWith($this->_record);

        $localUnit = $this->_record->getRecordAdapter();
        $application = $localUnit->getApplication();
        $bridgeUnit = $this->_getBridgeUnit($application);

        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();
        $bridgeAlias = $bridgeLocalFieldName.'Bridge';

        return opal\query\Initiator::factory($application)
            ->beginSelect(func_get_args())
            ->from($bridgeUnit, $bridgeAlias)

            // Add local primary key(s) as prerequisite
            ->wherePrerequisite($bridgeAlias.'.'.$bridgeLocalFieldName, '=', $this->_localPrimaryKeySet);
    }

    public function selectDistinctFromBridge($field1=null) {
        $query = call_user_func_array([$this, 'selectFromBridge'], func_get_args());
        $query->isDistinct(true);
        return $query;
    }
    
    public function fetch() {
        if(!$this->_record) {
            throw new opal\record\ValuePreparationException(
                'Cannot lookup relations, value container has not been prepared'
            );
        }

        $this->_localPrimaryKeySet->updateWith($this->_record);
        
        $localUnit = $this->_record->getRecordAdapter();
        $application = $localUnit->getApplication();
        
        $targetUnit = $this->_getTargetUnit($application);
        $bridgeUnit = $this->_getBridgeUnit($application);

        $localFieldName = $this->_field->getName();
        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();
        $bridgeTargetFieldName = $this->_field->getBridgeTargetFieldName();
        $bridgeAlias = $bridgeUnit->getUnitName();

        if(false !== strpos($bridgeAlias, '(')) {
            $bridgeAlias = $bridgeLocalFieldName.'Bridge';
        }

        return opal\query\Initiator::factory($application)
            ->beginFetch()
            ->from($targetUnit, $localFieldName)
        
            // Join bridge table as constraint
            ->joinConstraint()
                ->from($bridgeUnit, $bridgeAlias)
                ->on($bridgeAlias.'.'.$bridgeTargetFieldName, '=', $localFieldName.'.@primary')
                ->endJoin()

            // Add local primary key(s) as prerequisite
            ->wherePrerequisite($bridgeAlias.'.'.$bridgeLocalFieldName, '=', $this->_localPrimaryKeySet);
    }

    public function fetchFromBridge() {
        if(!$this->_record) {
            throw new opal\record\ValuePreparationException(
                'Cannot lookup relations, value container has not been prepared'
            );
        }

        $this->_localPrimaryKeySet->updateWith($this->_record);
        
        $localUnit = $this->_record->getRecordAdapter();
        $application = $localUnit->getApplication();
        $bridgeUnit = $this->_getBridgeUnit($application);

        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();
        $bridgeAlias = $bridgeLocalFieldName.'Bridge';
        
        return opal\query\Initiator::factory($application)
            ->beginFetch()
            ->from($bridgeUnit, $bridgeAlias)

            // Add local primary key(s) as prerequisite
            ->wherePrerequisite($bridgeAlias.'.'.$bridgeLocalFieldName, '=', $this->_localPrimaryKeySet);
    }

    public function getRelatedPrimaryKeys() {
        $bridgeTargetFieldName = $this->_field->getBridgeTargetFieldName();
        return $this->selectFromBridge($bridgeTargetFieldName)->toList($bridgeTargetFieldName);
    }
    
    
    
// Tasks
    public function deploySaveTasks(opal\record\task\ITaskSet $taskSet, opal\record\IRecord $parentRecord, $fieldName, opal\record\task\ITask $recordTask=null) {
        $localUnit = $parentRecord->getRecordAdapter();
        $this->_localPrimaryKeySet->updateWith($parentRecord);

        $application = $localUnit->getApplication();
        $targetUnit = $this->_getTargetUnit($application);
        $bridgeUnit = $this->_getBridgeUnit($application);

        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();
        $bridgeTargetFieldName = $this->_field->getBridgeTargetFieldName();

        $removeAllTask = null;
        

        // Save any changed populated records
        foreach($this->_current as $id => $record) {
            if($record instanceof opal\record\IRecord) {
                $record->deploySaveTasks($taskSet);
            }
        }
        
        
        
        // Remove all
        if($this->_removeAll && !$this->_localPrimaryKeySet->isNull()) {
            $removeAllTask = new opal\record\task\DeleteKey($bridgeUnit, [
                $bridgeLocalFieldName => $this->_localPrimaryKeySet
            ]);

            $taskSet->addTask($removeAllTask);
        }

        $filterKeys = array();
        
        // Insert relation tasks
        foreach($this->_new as $id => $record) {
            // Build bridge
            $bridgeRecord = $bridgeUnit->newRecord();
            $bridgeTask = $taskSet->insert($bridgeRecord)->ifNotExists(true);

            // Local ids
            $bridgeRecord->__set($bridgeLocalFieldName, $this->_localPrimaryKeySet);

            if($recordTask) {
                $bridgeTask->addDependency(
                    new opal\record\task\dependency\UpdateBridge($bridgeLocalFieldName, $recordTask)
                );
            }
            
            // Target key set
            if($record instanceof opal\record\IPrimaryKeySet) {
                $targetKeySet = $record;
            } else {
                $targetKeySet = $this->_targetPrimaryKeySet->duplicateWith($record);
            }

            // Filter remove all task
            if($removeAllTask) {
                foreach($targetKeySet->toArray() as $key => $value) {
                    $filterKeys[$bridgeTargetFieldName.'_'.$key][$id] = $value;
                }
            }
            

            // Target task
            $targetRecordTask = null;
            
            if($record instanceof opal\record\IRecord) {
                $targetRecordTask = $record->deploySaveTasks($taskSet);
            }
            
            // Target ids
            $bridgeRecord->__set($bridgeTargetFieldName, $targetKeySet);

            if($targetRecordTask) {
                $bridgeTask->addDependency(
                    new opal\record\task\dependency\UpdateBridge(
                        $bridgeTargetFieldName, 
                        $targetRecordTask
                    )
                );
            }
            

            // Remove-all dependency
            if($removeAllTask) {
                $bridgeTask->addDependency($removeAllTask);
            }
        }

        if($removeAllTask && !empty($filterKeys)) {
            $removeAllTask->setFilterKeys($filterKeys);
        }

        
        // Delete relation tasks
        if(!$removeAllTask && !empty($this->_remove) && !$this->_localPrimaryKeySet->isNull()) {
            foreach($this->_remove as $id => $record) {
                $bridgeData = array();
            
                foreach($this->_localPrimaryKeySet->toArray() as $key => $value) {
                    $bridgeData[$bridgeLocalFieldName.'_'.$key] = $value;
                }
                
                if($record instanceof opal\record\IPrimaryKeySet) {
                    $targetKeySet = $record;
                } else {
                    $targetKeySet = $this->_targetPrimaryKeySet->duplicateWith($record);
                }
                
                foreach($targetKeySet->toArray() as $key => $value) {
                    $bridgeData[$bridgeTargetFieldName.'_'.$key] = $value;
                }
                
                if(empty($bridgeData)) {
                    continue;
                }
                
                $taskSet->addTask(new opal\record\task\DeleteKey($bridgeUnit, $bridgeData));
            }
        }
        
        return $this;
    }
    
    public function acceptSaveTaskChanges(opal\record\IRecord $record) {
        $this->_current = array_merge($this->_current, $this->_new);
        $this->_new = array();
        $this->_remove = array();
        $this->_removeAll = false;
        
        return $this;
    }
    
    public function deployDeleteTasks(opal\record\task\ITaskSet $taskSet, opal\record\IRecord $parentRecord, $fieldName, opal\record\task\ITask $recordTask=null) {
        if(!$recordTask) {
            return $this;
        }
            
        $localUnit = $parentRecord->getRecordAdapter();
        $application = $localUnit->getApplication();
            
        $this->_localPrimaryKeySet->updateWith($parentRecord);
        $bridgeUnit = $this->_getBridgeUnit($application);

        $bridgeLocalFieldName = $this->_field->getBridgeLocalFieldName();
        $bridgeData = array();
            
        foreach($this->_localPrimaryKeySet->toArray() as $key => $value) {
            $bridgeData[$bridgeLocalFieldName.'_'.$key] = $value;
        }

        if(!empty($bridgeData)) {
            $taskSet->addTask(new opal\record\task\DeleteKey($bridgeUnit, $bridgeData));
        }
        
        return $this;
    }
    
    public function acceptDeleteTaskChanges(opal\record\IRecord $record) {
        $this->_new = array_merge($this->_current, $this->_new);
        $this->_current = array();
        $this->_remove = array();
        
        return $this;
    }
    

    public function populateInverse(array $inverse) {
        $this->_current = array_merge($this->_normalizeInputRecordList($inverse));
        return $this;
    }

    public function __toString() {
        return (string)count($this);
    }

    
// Dump
    public function getDumpValue() {
        if(empty($this->_current) && empty($this->_new) && empty($this->_remove)) {
            return implode(', ', $this->_localPrimaryKeySet->getFieldNames()).
            ' -> ['.implode(', ', $this->_targetPrimaryKeySet->getFieldNames()).']';
        }
        
        $output = $this->_current;
        
        foreach($this->_new as $id => $record) {
            $output['+ '.$id] = $record;
        }
        
        foreach($this->_remove as $id => $record) {
            $output['- '.$id] = $record;
        }
        
        return $output;
    }
}
