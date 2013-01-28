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
class Many extends axis\schema\field\Base implements axis\schema\IManyField {
    
    use axis\schema\TRelationField;
    use axis\schema\TBridgedRelationField;

    protected $_localPrimaryFields = array('id');
    protected $_targetPrimaryFields = array('id');
    
    public function __construct(axis\schema\ISchema $schema, $type, $name, array $args=null) {
        parent::__construct($schema, $type, $name, $args);
        $this->_bridgeUnitId = $this->_getBridgeUnitType().'('.$schema->getName().'.'.$this->_name.')';
    }
    
    protected function _init($targetTableUnit) {
        $this->setTargetUnitId($targetTableUnit);
    }
    
    
    
// Field names
    public function getLocalPrimaryFieldNames() {
        return $this->_localPrimaryFields;
    }
    
    public function getTargetPrimaryFieldNames() {
        return $this->_targetPrimaryFields;
    }
    
    
// Values
    public function inflateValueFromRow($key, array $row, opal\query\record\IRecord $forRecord=null) {
        $output = $this->sanitizeValue(null, $forRecord);

        if(isset($row[$key])) {
            $output->populateInverse($row[$key]);
        }

        return $output;
    }
    
    public function deflateValue($value) {
        return null;
    }
    
    public function sanitizeValue($value, $forRecord) {
        if($forRecord) {
            $output = new axis\unit\table\record\BridgedManyRelationValueContainer(
                $this->_bridgeUnitId, 
                $this->_targetUnitId,
                $this->_bridgeLocalFieldName, 
                $this->_bridgeTargetFieldName,
                $this->_localPrimaryFields, 
                $this->_targetPrimaryFields,
                true
            );

            if(is_array($value)) {
                foreach($value as $entry) {
                    $output->add($entry);
                }
            }

            return $output;
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
        
        $localFieldName = $this->getBridgeLocalFieldName();
        $targetFieldName = $this->getBridgeTargetFieldName();

        $query = $bridgeUnit->select(
            $localFieldName.' as id'
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
                    $query->where($targetFieldName, $operator, $value);
                } else {
                    foreach($value as $inVal) {
                        $targetManifest = new opal\query\record\PrimaryManifest(
                            $this->_targetPrimaryFields, $inVal
                        );
                        
                        $query->orWhere($targetFieldName, '=', $targetManifest);
                    }
                }
                
                break;
                
            default:
                $targetManifest = new opal\query\record\PrimaryManifest(
                    $this->_targetPrimaryFields, $value
                );

                $query->where($targetFieldName, $operator, $targetManifest);
                
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
    

// Populate
    public function rewritePopulateQueryToAttachment(opal\query\IPopulateQuery $populate) {
        $application = $populate->getSourceManager()->getApplication();

        $parentSource = $populate->getParentSource();
        $parentSourceAlias = $parentSource->getAlias();

        $targetSource = $populate->getSource();
        $targetSourceAlias = $targetSource->getAlias();

        $bridgeLocalFieldName = $this->getBridgeLocalFieldName();
        $bridgeTargetFieldName = $this->getBridgeTargetFieldName();

        $bridgeUnit = axis\Unit::fromId($this->_bridgeUnitId, $application);
        $bridgeSourceAlias = $populate->getFieldName().'_bridge';

        $output = opal\query\FetchAttach::fromPopulate($populate)
            ->rightJoinConstraint()
                ->from($bridgeUnit, $bridgeSourceAlias)
                ->on($bridgeSourceAlias.'.'.$bridgeTargetFieldName, '=', $targetSourceAlias.'.@primary')
                ->endJoin()
            ->on($bridgeSourceAlias.'.'.$bridgeLocalFieldName, '=', $parentSourceAlias.'.@primary');
            
        $output->asMany($this->_name);

        return $output;
    }

    
// Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $localSchema) {
        $this->_sanitizeTargetUnitId($localUnit);
        $this->_sanitizeBridgeUnitId($localUnit);


        // Local ids
        if(!$localPrimaryIndex = $localSchema->getPrimaryIndex()) {
            throw new axis\schema\RuntimeException(
                'Relation table '.$localUnit->getUnitId().' does not have a primary index'
            );
        }

        $this->_localPrimaryFields = array();
        
        foreach($localPrimaryIndex->getFields() as $name => $field) {
            if($field instanceof axis\schema\IMultiPrimitiveField) {
                foreach($field->getPrimitiveFieldNames() as $name) {
                    $this->_localPrimaryFields[] = $name;
                }
            } else {
                $this->_localPrimaryFields[] = $name;
            }
        }

        return $this;
    }
    
    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $localSchema) {
        $targetUnit = $this->_validateTargetUnit($localUnit);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetPrimaryIndex = $this->_validateTargetPrimaryIndex($targetUnit, $targetSchema);
        
        $this->_targetPrimaryFields = array_keys($targetPrimaryIndex->getFields());
        $this->_validateDefaultValue($localUnit, $this->_targetPrimaryFields);

        $bridgeUnit = $this->_validateBridgeUnit($localUnit);
        
        return $this;
    }
    
    
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_setRelationStorageArray($data);
        $this->_setBridgeRelationStorageArray($data);

        $this->_localPrimaryFields = (array)$data['lpf'];
        $this->_targetPrimaryFields = (array)$data['tpf'];
    }
    
    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getRelationStorageArray(),
            $this->_getBridgeRelationStorageArray(),
            [
                'lpf' => $this->_localPrimaryFields,
                'tpf' => $this->_targetPrimaryFields,
            ]
        );
    }
}
