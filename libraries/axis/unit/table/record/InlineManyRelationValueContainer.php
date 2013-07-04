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
    
    protected $_targetPrimaryManifest;
    protected $_field;
    protected $_record = null;
    
    public function __construct(axis\schema\IOneToManyField $field) {
        $this->_field = $field;
        $this->_targetPrimaryManifest = new opal\record\PrimaryManifest($field->getTargetPrimaryFieldNames());
    }
    
    public function isPrepared() {
        return $this->_record !== null;
    }
    
    public function prepareValue(opal\record\IRecord $record, $fieldName) {
        $this->_record = $record;
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
    

    public function getTargetUnitId() {
        return $this->_field->getTargetUnitId();
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
            $targetUnit = $this->_getTargetUnit($application);
            
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
        $targetUnit = $this->_getTargetUnit($application);
        
        // Init query
        $query = opal\query\Initiator::factory($application)
            ->beginSelect(func_get_args())
            ->from($targetUnit, $this->_field->getName());
                
                
        $manifest = $this->_record->getPrimaryManifest();
        $query->wherePrerequisite($this->_field->getName().'.'.$this->_field->getTargetField(), '=', $manifest);
        
        return $query;
    }

    public function selectDistinct($field1=null) {
        $query = call_user_func_array([$this, 'select'], func_get_args());
        $query->isDistinct(true);
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
        $targetUnit = $this->_getTargetUnit($application);
        
        // Init query
        $query = opal\query\Initiator::factory($application)
            ->beginFetch()
            ->from($targetUnit, $this->_field->getName());
            
        $manifest = $this->_record->getPrimaryManifest();
        $query->wherePrerequisite($this->_field->getName().'.'.$this->_field->getTargetField(), '=', $manifest);
        
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
        $targetUnit = $this->_getTargetUnit($application);
        $targetField = $this->_field->getTargetField();
        $parentManifest = $parentRecord->getPrimaryManifest();

        
        // Save any changed populated records
        foreach($this->_current as $id => $record) {
            if($record instanceof opal\record\IRecord) {
                $record->deploySaveTasks($taskSet);
            }
        }
        
        
        // Remove all
        $removeAllTask = null;

        if($this->_removeAll && !$parentRecord->isNew()) {
            $removeAllTask = $taskSet->addRawQuery(
                'rmRel:'.opal\record\Base::extractRecordId($parentRecord).'/'.$this->_field->getName(),
                $query = $targetUnit->update([$targetField => null])
                    ->where($targetField, '=', $parentManifest)
            );

            if(!empty($this->_new)) {
                $query->where('@primary', '!in', $this->_new);
            }
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
                            $targetField, $recordTask
                        )
                    );
                } else {
                    $record->set($targetField, $parentManifest);
                }
            } else {
                $values = array();
                
                foreach($parentManifest->toArray() as $key => $value) {
                    $values[$targetField.'_'.$key] = $value;
                }
                
                $targetRecordTask = new opal\record\task\UpdateRaw(
                    $targetUnit, $record, $values
                );
                
                $taskSet->addTask($targetRecordTask);
                
                if($recordTask) {
                    $targetRecordTask->addDependency(
                        new opal\record\task\dependency\UpdateRawManifest(
                            $targetField, $recordTask
                        )
                    );
                }
            }
        }

            
        // Remove relation tasks
        if(!empty($this->_remove) && !$removeAllTask) {
            $fields = array();
                
            foreach($parentManifest->toArray() as $key => $value) {
                $fields[] = $targetField.'_'.$key;
            }
            
            $nullManifest = new opal\record\PrimaryManifest($fields, null);
            
            foreach($this->_remove as $id => $record) {
                if($record instanceof opal\record\IPrimaryManifest) {
                    $targetManifest = $record;
                } else {
                    $targetManifest = $this->_targetPrimaryManifest->duplicateWith($record);
                }
                
                if($record instanceof opal\record\IRecord) {
                    $record->set($targetField, $nullManifest);
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
        $targetUnit = $this->_getTargetUnit($application);
        $targetField = $this->_field->getTargetField();
        $parentManifest = $parentRecord->getPrimaryManifest();
        $values = array();
        
        foreach($parentManifest->toArray() as $key => $value) {
            $values[$targetField.'_'.$key] = $value;
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

    public function __toString() {
        return (string)count($this);
    }
    

    
// Dump
    public function getDumpValue() {
        if(empty($this->_current) && empty($this->_new) && empty($this->_remove)) {
            return '['.$this->_field->getTargetField().']';
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
