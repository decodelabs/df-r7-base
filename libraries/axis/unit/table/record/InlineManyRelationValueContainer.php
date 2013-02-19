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

class InlineManyRelationValueContainer implements 
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
    
    protected $_targetUnitId;
    protected $_targetField;
    protected $_targetPrimaryManifest;
    protected $_localField;
    protected $_record = null;
    
    public function __construct($targetUnitId, $targetField, array $targetPrimaryFields) {
        $this->_targetUnitId = $targetUnitId;
        $this->_targetField = $targetField;
        $this->_targetPrimaryManifest = new opal\record\PrimaryManifest($targetPrimaryFields);
    }
    
    public function isPrepared() {
        return $this->_record !== null;
    }
    
    public function prepareValue(opal\record\IRecord $record, $fieldName) {
        $this->_record = $record;
        $this->_localField = $fieldName;
        
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
    
    public function populateInverse(opal\record\IRecord $record=null) {
        if($record) {
            $id = opal\record\Base::extractRecordId($record);
            $this->_current[$id] = $record;
        }
        
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

    protected function _normalizeInputRecordList(array $records) {
        $index = array();
        $lookupManifests = array();
        
        foreach($records as $record) {
            if($record instanceof opal\record\IRecord) {
                $id = opal\record\Base::extractRecordId($record);
            } else if($record instanceof opal\record\IPrimaryManifest) {
                $id = opal\record\Base::extractRecordId($record);
                $lookupManifests[$id] = $record;
            } else {
                $record = $this->_targetPrimaryManifest->duplicateWith($record);
                $id = opal\record\Base::extractRecordId($record);
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
    
    public function remove($record) {
        return $this->removeList(func_get_args());
    }
    
    public function removeList(array $records) {
        $index = array();
        
        foreach($records as $record) {
            if($record instanceof opal\record\IRecord) {
                $id = opal\record\Base::extractRecordId($record);
            } else if($record instanceof opal\record\IPrimaryManifest) {
                $id = opal\record\Base::extractRecordId($record);
            } else {
                $record = $this->_targetPrimaryManifest->duplicateWith($record);
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
    

// Query
    public function select($field1=null) {
        if(!$this->_record) {
            throw new opal\record\ValuePreparationException(
                'Cannot lookup relations, value container has not been prepared'
            );
        }
        
        $localUnit = $this->_record->getRecordAdapter();
        $localSchema = $localUnit->getUnitSchema();
        $application = $localUnit->getApplication();
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $application);
        
        // Init query
        $query = opal\query\Initiator::factory($application)
            ->beginSelect(func_get_args())
            ->from($targetUnit, $this->_localField);
                
                
        $manifest = $this->_record->getPrimaryManifest();
        $query->wherePrerequisite($this->_localField.'.'.$this->_targetField, '=', $manifest);
        
        return $query;
    }
    
    public function fetch() {
        if(!$this->_record) {
            throw new opal\record\ValuePreparationException(
                'Cannot lookup relations, value container has not been prepared'
            );
        }
        
        $localUnit = $this->_record->getRecordAdapter();
        $localSchema = $localUnit->getUnitSchema();
        $application = $localUnit->getApplication();
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $application);
        
        // Init query
        $query = opal\query\Initiator::factory($application)
            ->beginFetch()
            ->from($targetUnit, $this->_localField);
            
        $manifest = $this->_record->getPrimaryManifest();
        $query->wherePrerequisite($this->_localField.'.'.$this->_targetField, '=', $manifest);
        
        return $query;
    }

    public function getRelatedPrimaryKeys() {
        if(!$this->_record) {
            throw new opal\record\ValuePreparationException(
                'Cannot lookup relations, value container has not been prepared'
            );
        }

        return $this->select('@primary')->toList('@primary');
    }
    
    
// Tasks
    public function deploySaveTasks(opal\record\task\ITaskSet $taskSet, opal\record\IRecord $parentRecord, $fieldName, opal\record\task\ITask $recordTask=null) {
        $localUnit = $parentRecord->getRecordAdapter();
        $application = $localUnit->getApplication();
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $application);
        $parentManifest = $parentRecord->getPrimaryManifest();
        
        // Save any changed populated records
        foreach($this->_current as $id => $record) {
            if($record instanceof opal\record\IRecord) {
                $record->deploySaveTasks($taskSet);
            }
        }
        
        
        // Remove all
        if($this->_removeAll) {
            core\stub('Complete remove all functionality');
            // TODO: set all inverse ids to null
        }
        
        
        // Insert relation tasks
        foreach($this->_new as $id => $record) {
            if($record instanceof opal\record\IPrimaryManifest) {
                $targetManifest = $record;
            } else {
                $targetManifest = $this->_targetPrimaryManifest->duplicateWith($record);
            }
            
            if($record instanceof opal\record\IRecord) {
                if(!$targetRecordTask = $record->deploySaveTasks($taskSet)) {
                    $targetRecordTask = $taskSet->update($record);
                }
                
                if($recordTask) {
                    $targetRecordTask->addDependency(
                        new opal\record\task\dependency\UpdateManifestField(
                            $this->_targetField, $recordTask
                        )
                    );
                } else {
                    $record->set($this->_targetField, $parentManifest);
                }
            } else {
                $values = array();
                
                foreach($parentManifest->toArray() as $key => $value) {
                    $values[$this->_targetField.'_'.$key] = $value;
                }
                
                $targetRecordTask = new opal\record\task\UpdateRaw(
                    $targetUnit, $record, $values
                );
                
                $taskSet->addTask($targetRecordTask);
                
                if($recordTask) {
                    $targetRecordTask->addDependency(
                        new opal\record\task\dependency\UpdateRawManifest(
                            $this->_targetField, $recordTask
                        )
                    );
                }
            }
        }

            
        // Remove relation tasks
        if(!empty($this->_remove)) {
            $fields = array();
                
            foreach($parentManifest->toArray() as $key => $value) {
                $fields[] = $this->_targetField.'_'.$key;
            }
            
            $nullManifest = new opal\record\PrimaryManifest($fields, null);
            
            foreach($this->_remove as $id => $record) {
                if($record instanceof opal\record\IPrimaryManifest) {
                    $targetManifest = $record;
                } else {
                    $targetManifest = $this->_targetPrimaryManifest->duplicateWith($record);
                }
                
                if($record instanceof opal\record\IRecord) {
                    $record->set($this->_targetField, $nullManifest);
                    $record->deploySaveTasks($taskSet);
                } else {
                    $targetRecordTask = new opal\record\task\UpdateRaw(
                        $targetUnit, $record, $nullManifest->toArray()
                    );
                    
                    $taskSet->addTask($targetRecordTask);
                }
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
        $localUnit = $parentRecord->getRecordAdapter();
        $application = $localUnit->getApplication();
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $application);
        $parentManifest = $parentRecord->getPrimaryManifest();
        $values = array();
        
        foreach($parentManifest->toArray() as $key => $value) {
            $values[$this->_targetField.'_'.$key] = $value;
        }
        
        $inverseManifest = new opal\record\PrimaryManifest(array_keys($values), $values);
        
        $targetRecordTask = new opal\record\task\UpdateRaw(
            $targetUnit, $inverseManifest, $inverseManifest->duplicateWith(null)->toArray()
        );

        if(!$taskSet->hasTask($targetRecordTask)) {  
            $taskSet->addTask($targetRecordTask);
        
            if($recordTask) {
                $recordTask->addDependency($targetRecordTask);
            }
        }
        
        return $this;
    }
    
    public function acceptDeleteTaskChanges(opal\record\IRecord $record) {
        $this->_new = array_merge($this->_current, $this->_new);
        $this->_current = array();
        $this->_remove = array();
        
        return $this;
    }
    

    
// Dump
    public function getDumpValue() {
        if(empty($this->_current) && empty($this->_new) && empty($this->_remove)) {
            return '['.$this->_targetField.']';
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
