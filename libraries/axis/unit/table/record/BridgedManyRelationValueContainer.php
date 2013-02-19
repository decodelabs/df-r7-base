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
    
    protected $_bridgeUnitId;
    protected $_targetUnitId;
    protected $_bridgeLocalFieldName;
    protected $_bridgeTargetFieldName;
    protected $_localField;
    protected $_localPrimaryManifest;
    protected $_targetPrimaryManifest;
    protected $_isDominant;
    
    protected $_record = null;
    
    public function __construct($bridgeUnitId, $targetUnitId, $bridgeLocalFieldName, $bridgeTargetFieldName, array $localPrimaryFields, array $targetPrimaryFields, $isDominant=true) {
        $this->_bridgeUnitId = $bridgeUnitId;
        $this->_targetUnitId = $targetUnitId;
        $this->_bridgeLocalFieldName = $bridgeLocalFieldName;
        $this->_bridgeTargetFieldName = $bridgeTargetFieldName;
        $this->_isDominant = $isDominant;
        
        $this->_localPrimaryManifest = new opal\record\PrimaryManifest($localPrimaryFields);
        $this->_targetPrimaryManifest = new opal\record\PrimaryManifest($targetPrimaryFields);
    }
    
    public function isPrepared() {
        return $this->_record !== null;
    }
    
    public function prepareValue(opal\record\IRecord $record, $fieldName) {
        $this->_localField = $fieldName;
        $this->_record = $record;
        $this->_localPrimaryManifest = $record->getPrimaryManifest();
        
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
    
    
// Manifests
    public function getLocalPrimaryManifest() {
        return $this->_localPrimaryManifest;
    }
    
    public function getTargetPrimaryManifest() {
        return $this->_targetPrimaryManifest;
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
            $this->_record->markAsChanged($this->_localField);
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
        $index = array();
        $lookupManifests = array();
        
        foreach($records as $record) {
            if($record instanceof opal\record\IRecord) {
                $id = opal\record\task\Base::extractRecordId($record);
            } else if($record instanceof opal\record\IPrimaryManifest) {
                $id = opal\record\task\Base::extractRecordId($record);
            } else {
                $record = $this->_targetPrimaryManifest->duplicateWith($record);
                $id = opal\record\task\Base::extractRecordId($record);
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
            $this->_record->markAsChanged($this->_localField);
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
        $lookupManifests = array();
        
        foreach($records as $record) {
            if($record instanceof opal\record\IRecord) {
                $id = opal\record\task\Base::extractRecordId($record);
            } else if($record instanceof opal\record\IPrimaryManifest) {
                $id = opal\record\task\Base::extractRecordId($record);
                $lookupManifests[$id] = $record;
            } else {
                $record = $this->_targetPrimaryManifest->duplicateWith($record);
                $id = opal\record\task\Base::extractRecordId($record);
                $lookupManifests[$id] = $record;
            }
            
            if(!isset($this->_current[$id])) {
                $index[(string)$id] = $record;
            }
        }
        
        if(!empty($lookupManifests)) {
            $application = $this->_record->getRecordAdapter()->getApplication();
            $targetUnit = axis\Unit::fromId($this->_targetUnitId, $application);
            
            
            $query = opal\query\Initiator::factory($application)
                ->beginSelect($this->_targetPrimaryManifest->getFieldNames())
                ->from($targetUnit);
            
            foreach($lookupManifests as $manifest) {
                $clause = $query->beginOrWhereClause();
                
                foreach($manifest->toArray() as $key => $value) {
                    $clause->where($key, '=', $value);
                }
                
                $clause->endClause();
            }
            
            $res = $query->toArray();
            
            foreach($lookupManifests as $id => $manifest) {
                if(empty($res)) {
                    unset($index[$id]);
                    continue;
                }
                
                $keys = $manifest->toArray();
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
        
        return axis\Unit::fromId($this->_bridgeUnitId, $application);
    }

    public function getTargetUnit() {
        $application = null;

        if($this->_record) {
            $application = $this->_record->getRecordAdapter()->getApplication();
        }
        
        return axis\Unit::fromId($this->_targetUnitId, $application);
    }

    public function getBridgeLocalFieldName() {
        return $this->_bridgeLocalFieldName;
    }

    public function getBridgeTargetFieldName() {
        return $this->_bridgeTargetFieldName;
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
        
        $localUnit = $this->_record->getRecordAdapter();
        $application = $localUnit->getApplication();
        
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $application);
        $bridgeUnit = axis\Unit::fromId($this->_bridgeUnitId, $application);

        $bridgeAlias = $this->_bridgeLocalFieldName.'Bridge';
        
        return opal\query\Initiator::factory($application)
            ->beginSelect(func_get_args())
            ->from($targetUnit, $this->_localField)
        
            // Join bridge table as constraint
            ->join($bridgeUnit->getBridgeFieldNames($this->_localField))
                ->from($bridgeUnit, $bridgeAlias)
                ->on($bridgeAlias.'.'.$this->_bridgeTargetFieldName, '=', $this->_localField.'.@primary')
                ->endJoin()

            // Add local primary key(s) as prerequisite
            ->wherePrerequisite($bridgeAlias.'.'.$this->_bridgeLocalFieldName, '=', $this->_localPrimaryManifest);
    }

    public function selectFromBridge($field1=null) {
        if(!$this->_record) {
            throw new opal\record\ValuePreparationException(
                'Cannot lookup relations, value container has not been prepared'
            );
        }
        
        $localUnit = $this->_record->getRecordAdapter();
        $application = $localUnit->getApplication();
        $bridgeUnit = axis\Unit::fromId($this->_bridgeUnitId, $application);
        $bridgeAlias = $this->_bridgeLocalFieldName.'Bridge';
        
        return opal\query\Initiator::factory($application)
            ->beginSelect(func_get_args())
            ->from($bridgeUnit, $bridgeAlias)

            // Add local primary key(s) as prerequisite
            ->wherePrerequisite($bridgeAlias.'.'.$this->_bridgeLocalFieldName, '=', $this->_localPrimaryManifest);
    }
    
    public function fetch() {
        if(!$this->_record) {
            throw new opal\record\ValuePreparationException(
                'Cannot lookup relations, value container has not been prepared'
            );
        }
        
        $localUnit = $this->_record->getRecordAdapter();
        $application = $localUnit->getApplication();
        
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $application);
        $bridgeUnit = axis\Unit::fromId($this->_bridgeUnitId, $application);
        
        $bridgeAlias = $this->_bridgeLocalFieldName.'Bridge';

        return opal\query\Initiator::factory($application)
            ->beginFetch()
            ->from($targetUnit, $this->_localField)
        
            // Join bridge table as constraint
            ->joinConstraint()
                ->from($bridgeUnit, $bridgeAlias)
                ->on($bridgeAlias.'.'.$this->_bridgeTargetFieldName, '=', $this->_localField.'.@primary')
                ->endJoin()

            // Add local primary key(s) as prerequisite
            ->wherePrerequisite($bridgeAlias.'.'.$this->_bridgeLocalFieldName, '=', $this->_localPrimaryManifest);
    }

    public function fetchFromBridge() {
        if(!$this->_record) {
            throw new opal\record\ValuePreparationException(
                'Cannot lookup relations, value container has not been prepared'
            );
        }
        
        $localUnit = $this->_record->getRecordAdapter();
        $application = $localUnit->getApplication();
        $bridgeUnit = axis\Unit::fromId($this->_bridgeUnitId, $application);
        $bridgeAlias = $this->_bridgeLocalFieldName.'Bridge';
        
        return opal\query\Initiator::factory($application)
            ->beginFetch()
            ->from($bridgeUnit, $bridgeAlias)

            // Add local primary key(s) as prerequisite
            ->wherePrerequisite($bridgeAlias.'.'.$this->_bridgeLocalFieldName, '=', $this->_localPrimaryManifest);
    }

    public function getRelatedPrimaryKeys() {
        return $this->selectFromBridge($this->_bridgeTargetFieldName)
            ->toList($this->_bridgeTargetFieldName);
    }
    
    
    
// Tasks
    public function deploySaveTasks(opal\record\task\ITaskSet $taskSet, opal\record\IRecord $parentRecord, $fieldName, opal\record\task\ITask $recordTask=null) {
        $localUnit = $parentRecord->getRecordAdapter();
        $this->_localPrimaryManifest->updateWith($parentRecord);

        $application = $localUnit->getApplication();
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $application);
        $bridgeUnit = axis\Unit::fromId($this->_bridgeUnitId, $application);

        $removeAllTask = null;
        

        // Save any changed populated records
        foreach($this->_current as $id => $record) {
            if($record instanceof opal\record\IRecord) {
                $record->deploySaveTasks($taskSet);
            }
        }
        
        
        
        // Remove all
        if($this->_removeAll) {
            $bridgeData = array();
            
            foreach($this->_localPrimaryManifest->toArray() as $key => $value) {
                $bridgeData[$this->_bridgeLocalFieldName.'_'.$key] = $value;
            }
            
            if(!empty($bridgeData)) {
                $removeAllTask = new opal\record\task\DeleteKey($bridgeUnit, $bridgeData);
                $taskSet->addTask($removeAllTask);
            }
        }


        
        // Insert relation tasks
        foreach($this->_new as $id => $record) {
            // Build bridge
            $bridgeRecord = $bridgeUnit->newRecord();
            $bridgeTask = $taskSet->replace($bridgeRecord);

            // Local ids
            $bridgeRecord->__set($this->_bridgeLocalFieldName, $this->_localPrimaryManifest);
            
            if($recordTask) {
                $bridgeTask->addDependency(
                    new opal\record\task\dependency\UpdateBridge($this->_bridgeLocalFieldName, $recordTask)
                );
            }
            
            // Target manifest
            if($record instanceof opal\record\IPrimaryManifest) {
                $targetManifest = $record;
            } else {
                $targetManifest = $this->_targetPrimaryManifest->duplicateWith($record);
            }
            

            // Target task
            $targetRecordTask = null;
            
            if($record instanceof opal\record\IRecord) {
                $targetRecordTask = $record->deploySaveTasks($taskSet);
            }
            
            // Target ids
            $bridgeRecord->__set($this->_bridgeTargetFieldName, $targetManifest);            
            
            if($targetRecordTask) {
                $bridgeTask->addDependency(
                    new opal\record\task\dependency\UpdateBridge(
                        $this->_bridgeTargetFieldName, 
                        $targetRecordTask
                    )
                );
            }
            

            // Remove-all dependency
            if($removeAllTask) {
                $bridgeTask->addDependency(
                    new opal\record\task\dependency\Base('*removeAll*', $removeAllTask)
                );
            }
        }

        
        // Delete relation tasks
        if(!$removeAllTask && !empty($this->_remove) && !$this->_localPrimaryManifest->isNull()) {
            foreach($this->_remove as $id => $record) {
                $bridgeData = array();
            
                foreach($this->_localPrimaryManifest->toArray() as $key => $value) {
                    $bridgeData[$this->_bridgeLocalFieldName.'_'.$key] = $value;
                }
                
                if($record instanceof opal\record\IPrimaryManifest) {
                    $targetManifest = $record;
                } else {
                    $targetManifest = $this->_targetPrimaryManifest->duplicateWith($record);
                }
                
                foreach($targetManifest->toArray() as $key => $value) {
                    $bridgeData[$this->_bridgeTargetFieldName.'_'.$key] = $value;
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
            
        $this->_localPrimaryManifest->updateWith($parentRecord);
        $bridgeUnit = axis\Unit::fromId($this->_bridgeUnitId, $application);
        $bridgeData = array();
            
        foreach($this->_localPrimaryManifest->toArray() as $key => $value) {
            $bridgeData[$this->_bridgeLocalFieldName.'_'.$key] = $value;
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

    
// Dump
    public function getDumpValue() {
        if(empty($this->_current) && empty($this->_new) && empty($this->_remove)) {
            return implode(', ', $this->_localPrimaryManifest->getFieldNames()).
            ' -> ['.implode(', ', $this->_targetPrimaryManifest->getFieldNames()).']';
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
