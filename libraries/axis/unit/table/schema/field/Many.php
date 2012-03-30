<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\table\schema\field;

use df;
use df\core;
use df\axis;
use df\opal;


/*
 * This type, like One does not care about inverses.
 * A relation table is always required
 * Should return a null primitive as key will always be on relation table
 */
class Many extends axis\schema\field\Base implements IManyField {
    
    protected $_localPrimaryFields = array('id');
    protected $_targetPrimaryFields = array('id');
    
    protected $_bridgeUnitId;
    protected $_targetUnitId;
    
    public function __construct(axis\schema\ISchema $schema, $type, $name, array $args) {
        parent::__construct($schema, $type, $name, $args);
        $this->_bridgeUnitId = 'table.ManyBridge('.$schema->getName().'.'.$this->_name.')';
    }
    
    protected function _init($targetTableUnit) {
        $this->setTargetUnitId($targetTableUnit);
    }
    
    
// Target unit id
    public function setTargetUnitId($targetUnit) {
        if($targetUnit instanceof axis\IUnit) {
            $targetUnit = $targetUnit->getUnitId();
        }
        
        $targetUnit = (string)$targetUnit;
        
        if($targetUnit != $this->_targetUnitId) {
            $this->_hasChanged = true;
        }
        
        $this->_targetUnitId = $targetUnit;
        return $this;
    }
    
    public function getTargetUnitId() {
        return $this->_targetUnitId;
    }
    
    
// Bridge unit id
    public function setBridgeUnitId($id) {
        if($id instanceof axis\IUnit) {
            $id = $id->getUnitId();
        }
        
        if($id != $this->_bridgeUnitId) {
            $this->_hasChanged = true;
        }
        
        $this->_bridgeUnitId = $id;
        return $this;
    }
    
    public function getBridgeUnitId() {
        return $this->_bridgeUnitId;
    }
    
    public function getBridgeUnit(core\IApplication $application=null) {
        return axis\Unit::fromId($this->_bridgeUnitId, $application);
    }
    
    
    
// Field names
    public function getLocalPrimaryFieldNames() {
        return $this->_localPrimaryFields;
    }
    
    public function getTargetPrimaryFieldNames() {
        return $this->_targetPrimaryFields;
    }
    
    
// Values
    public function inflateValueFromRow($key, array $row, $forRecord) {
        return $this->sanitizeValue(null, $forRecord);
    }
    
    public function deflateValue($value) {
        return null;
    }
    
    public function sanitizeValue($value, $forRecord) {
        if($forRecord) {
            return new axis\unit\table\record\BridgedManyRelationValueContainer(
                $this->_bridgeUnitId, 
                $this->_targetUnitId,
                $this->_localPrimaryFields, 
                $this->_targetPrimaryFields,
                true
            );
        } else {
            return $value;
        }
    }
    
    public function generateInsertValue(array $row) {
        return null;
    }
    
// Clause
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false) {
        if(count($this->_localPrimaryFields) != 1) {
            throw new RuntimeException(
                'Query clause on field '.$this->_name.' cannot be executed as it relies on a multi-field primary key. '.
                'You should probably use a fieldless join constraint instead'
            );
        }
        
        $sourceManager = $parent->getSourceManager();
        $source = $field->getSource();
        $localUnit = $source->getAdapter();
        $application = $localUnit->getApplication();
        
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $application);
        $targetField = $sourceManager->extrapolateIntrinsicField($source, $this->_localPrimaryFields[0]);
        
        $bridgeUnit = axis\Unit::fromId($this->_bridgeUnitId, $application);
        
        $localFieldPrefix = $bridgeUnit->getFieldPrefix($localUnit, true);
        $targetFieldPrefix = $bridgeUnit->getFieldPrefix($targetUnit, false);
        
        $query = $bridgeUnit->select(
            $localFieldPrefix.$this->_localPrimaryFields[0].' as id'
        );
            
        $mainOperator = 'in';
        
        if(opal\query\clause\Clause::isNegatedOperator($operator)) {
            $mainOperator = '!in';
            $operator = opal\query\clause\Clause::negateOperator($operator);
        } else {
            $operator = opal\query\clause\Clause::normalizeOperator($operator);
        }
        
        switch($operator) {
            case opal\query\clause\Clause::OP_IN:
                if($isSingleField = count($this->_targetPrimaryFields) == 1) {
                    $query->where($targetFieldPrefix.$this->_targetPrimaryFields[0], $operator, $value);
                } else {
                    foreach($value as $inVal) {
                        $targetManifest = new opal\query\record\PrimaryManifest(
                            $this->_targetPrimaryFields, $inVal
                        );
                        
                        $subClause = $query->beginOrWhereClause();
                        
                        foreach($targetManifest->toArray() as $key => $clauseVal) {
                            $subClause->where($targetFieldPrefix.$key, '=', $clauseVal);
                        }
                        
                        $subClause->endClause();
                    }
                }
                
                break;
                
            default:
                $targetManifest = new opal\query\record\PrimaryManifest(
                    $this->_targetPrimaryFields, $value
                );
            
                foreach($targetManifest->toArray() as $key => $clauseVal) {
                    $query->where($targetFieldPrefix.$key, $operator, $clauseVal);
                }
                
                break;
        }
        
        return opal\query\clause\Clause::factory(
            $parent,
            $targetField, 
            $mainOperator,
            $query,
            $isOr
        );
    }
    
    
// Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        $model = $unit->getModel();
        
        // Target unit id
        if(false === strpos($this->_targetUnitId, axis\Unit::ID_SEPARATOR)) {
            $this->_targetUnitId = $model->getModelName().axis\Unit::ID_SEPARATOR.$this->_targetUnitId;
        }
        
        // Bridge unit id
        if(empty($this->_bridgeUnitId)) {
            $this->_bridgeUnitId = $model->getModelName().axis\Unit::ID_SEPARATOR.'table.ManyBridge('.$unit->getUnitName().'.'.$this->_name.')';
        }
        
        if(false === strpos($this->_bridgeUnitId, axis\Unit::ID_SEPARATOR)) {
            $this->_bridgeUnitId = $model->getModelName().axis\Unit::ID_SEPARATOR.$this->_bridgeUnitId;
        }
        
        return $this;
    }
    
    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema) {
        // Local ids
        $localSchema = $localUnit->getTransientUnitSchema();
        
        if(!$localPrimaryIndex = $localSchema->getPrimaryIndex()) {
            throw new axis\schema\RuntimeException(
                'Relation table '.$localUnit->getUnitId().' does not have a primary index'
            );
        }
        
        $this->_localPrimaryFields = array_keys($localPrimaryIndex->getFields());
        
        
        // Target ids
        $targetUnit = axis\Unit::fromId($this->_targetUnitId, $localUnit->getApplication());
        
        if(!$targetUnit instanceof axis\unit\table\Base) {
            throw new axis\schema\RuntimeException(
                'Relation target unit '.$targetUnit->getUnitId().' is not a table'
            );
        }
        
        $targetSchema = $targetUnit->getTransientUnitSchema();
        
        if(!$targetPrimaryIndex = $targetSchema->getPrimaryIndex()) {
            throw new axis\schema\RuntimeException(
                'Relation table '.$targetUnit->getUnitId().' does not have a primary index'
            );
        }
        
        $this->_targetPrimaryFields = array_keys($targetPrimaryIndex->getFields());
        
        
        // Bridge table
        $bridgeUnit = axis\Unit::fromId($this->_bridgeUnitId, $localUnit->getApplication());
        $bridgeSchema = $bridgeUnit->getTransientUnitSchema();
        
        if($bridgeUnit->getModel()->getModelName() != $localUnit->getModel()->getModelName()) {
            throw new axis\schema\RuntimeException(
                'Bridge units must be local to the dominant participant - '.
                $this->_bridgeUnitId.' should be on model '.$localUnit->getModel()->getModelName()
            );
        }
        
        // TODO: validate default value
        
        return $this;
    }
    
    
// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_Null($this);
    }
}
