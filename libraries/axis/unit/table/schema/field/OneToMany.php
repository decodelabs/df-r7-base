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
 * This type requires an inverse field and must lookup and match target table.
 * Key resides on Many side, null primitive
 */
class OneToMany extends axis\schema\field\Base implements axis\schema\IOneToManyField {
    
    use axis\schema\TRelationField;
    use axis\schema\TInverseRelationField;
    use axis\schema\TTargetPrimaryFieldAwareRelationField;

    protected function _init($targetUnit, $targetField=null) {
        $this->setTargetUnitId($targetUnit);
        $this->setTargetField($targetField);
    }
    
    
// Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null) {
        $value = null;

        if(isset($row[$key])) {
            $value = $row[$key];
        }

        if($forRecord) {
            $output = $this->sanitizeValue(null, $forRecord);

            if(is_array($value)) {
                $output->populateList($value);
            }
        } else {
            $output = $value;
        }

        return $output;
    }
    
    public function deflateValue($value) {
        return null;
    }
    
    public function sanitizeValue($value, opal\record\IRecord $forRecord=null) {
        if($forRecord) {
            $output = new axis\unit\table\record\InlineManyRelationValueContainer($this);
            $output->prepareToSetValue($forRecord, $this->_name);

            if(is_array($value)) {
                $output->addList($value);
            } else if($value !== null) {
                $output->add($value);
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
        // TODO: rewrite virtual clause
        core\stub($parent, $field, $operator, $value);
    }

// Populate
    public function rewritePopulateQueryToAttachment(opal\query\IPopulateQuery $populate) {
        $output = opal\query\FetchAttach::fromPopulate($populate);

        $parentSourceAlias = $populate->getParentSourceAlias();
        $targetSourceAlias = $populate->getSourceAlias();
        
        $output->on($targetSourceAlias.'.'.$this->_targetField, '=', $parentSourceAlias.'.@primary');
        $output->asMany($this->_name);

        return $output;
    }
    

// Validate
    public function sanitize(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $schema) {
        $this->_sanitizeTargetUnitId($localUnit);
        
        return $this;
    }
    
    public function validate(axis\ISchemaBasedStorageUnit $localUnit, axis\schema\ISchema $localSchema) {
        // Local
        $localPrimaryIndex = $this->_validateLocalPrimaryIndex($localUnit, $localSchema);
        
        
        // Target
        $targetUnit = $this->_validateTargetUnit($localUnit);
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetPrimaryIndex = $this->_validateTargetPrimaryIndex($targetUnit, $targetSchema);
        $targetField = $this->_validateInverseRelationField($targetUnit, $targetSchema);
        $this->_validateDefaultValue($localUnit);
        
        return $this;
    }
    
    
    
// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_setRelationStorageArray($data);
        $this->_setInverseRelationStorageArray($data);
    }
    
    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            $this->_getRelationStorageArray(),
            $this->_getInverseRelationStorageArray()
        );
    }
}
