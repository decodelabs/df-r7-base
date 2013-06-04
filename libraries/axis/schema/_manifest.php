<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema;

use df;
use df\core;
use df\axis;
use df\opal;

// Exceptions
interface IException extends axis\IException, opal\schema\IException {}
class RuntimeException extends \RuntimeException implements IException {}
class FieldTypeNotFoundException extends RuntimeException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}
class LogicException extends \LogicException implements IException {}



// Interfaces
interface ISchema extends opal\schema\ISchema, opal\schema\IFieldProvider, opal\schema\IIndexProvider, opal\schema\IIndexedFieldProvider {
    public function getUnitType();
    public function getUnitId();
    public function iterateVersion();
    public function getVersion();
    
    public function sanitize(axis\ISchemaBasedStorageUnit $unit);
    public function validate(axis\ISchemaBasedStorageUnit $unit);
}




interface IField extends opal\schema\IField, opal\query\IFieldValueProcessor {
    public function getFieldTypeDisplayName();
    public function getFieldSchemaString();
    
    public function generateInsertValue(array $row);
    public function duplicateForRelation(axis\ISchemaBasedStorageUnit $unit, ISchema $schema);
    
    public function sanitize(axis\ISchemaBasedStorageUnit $unit, ISchema $schema);
    public function validate(axis\ISchemaBasedStorageUnit $unit, ISchema $schema);
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, ISchema $schema);
}


interface IAutoIndexField extends IField {}
interface IAutoUniqueField extends IAutoIndexField {}
interface IAutoPrimaryField extends IAutoUniqueField {}


interface IDateField extends IField {}

interface ILengthRestrictedField extends IField, opal\schema\ILengthRestrictedField {
    public function isConstantLength($flag=null);
}


trait TLengthRestrictedField {
    
    use opal\schema\TField_LengthRestricted;
    
    protected $_isConstantLength = false;
    
    public function isConstantLength($flag=null) {
        if($flag !== null) {
            $flag = (bool)$flag;
            
            if($flag !== $this->_isConstantLength) {
                $this->_hasChanged = true;
            }
            
            $this->_isConstantLength = $flag;
            return $this;
        }
        
        return $this->_isConstantLength;
    }
    
// Ext. serialize
    protected function _setLengthRestrictedStorageArray(array $data) {
        $this->_length = $data['lnt'];
        $this->_isConstantLength = $data['ctl'];
    }

    protected function _getLengthRestrictedStorageArray() {
        return [
            'lnt' => $this->_length,
            'ctl' => $this->_isConstantLength
        ];
    }
}


interface IAutoGeneratorField extends IField {
    public function shouldAutoGenerate($flag=null);
}


trait TAutoGeneratorField {
    
    protected $_autoGenerate = true;
    
    public function shouldAutoGenerate($flag=null) {
        if($flag !== null) {
            $flag = (bool)$flag;
            
            if($flag != $this->_autoGenerate) {
                $this->_hasChanged = true;
            }
            
            $this->_autoGenerate = (bool)$flag;
            return $this;
        }
        
        return $this->_autoGenerate;
    }
}


interface IMultiPrimitiveField extends IField {
    public function getPrimitiveFieldNames();
}

interface INullPrimitiveField extends IField {}

interface IQueryClauseRewriterField extends IField {
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false);
}

interface IRelationField extends IField, IQueryClauseRewriterField {
    public function setTargetUnitId($targetUnitId);
    public function getTargetUnitId();
    public function getTargetUnit(core\IApplication $application=null);
    public function rewritePopulateQueryToAttachment(opal\query\IPopulateQuery $populate);
}


trait TRelationField {

    protected $_targetUnitId;

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

    public function getTargetUnit(core\IApplication $application=null) {
        return axis\Model::loadUnitFromId($this->_targetUnitId, $application);
    }


    public function rewritePopulateQueryToAttachment(opal\query\IPopulateQuery $populate) {
        return null;
    }


    protected function _sanitizeTargetUnitId(axis\ISchemaBasedStorageUnit $unit) {
        $model = $unit->getModel();
        
        if(false === strpos($this->_targetUnitId, axis\IUnit::ID_SEPARATOR)) {
            $this->_targetUnitId = $model->getModelName().axis\IUnit::ID_SEPARATOR.$this->_targetUnitId;
        }
    }

    protected function _validateTargetUnit(axis\ISchemaBasedStorageUnit $localUnit) {
        $targetUnit = axis\Model::loadUnitFromId($this->_targetUnitId, $localUnit->getApplication());
        
        if($targetUnit->getUnitType() != $localUnit->getUnitType()) {
            throw new RuntimeException(
                'Relation target unit '.$targetUnit->getUnitId().' does not match local unit '.$localUnit->getUnitId().' type ('.$localUnit->getUnitType().')'
            );
        }

        /*
        if($this instanceof IBridgedRelationField
        && $targetUnit->getUnitId() == $localUnit->getUnitId()) {
            throw new RuntimeException(
                'Bridged relation targets cannot currently reference the local unit ('.$localUnit->getUnitId().')'
            );
        }
        */

        return $targetUnit;
    }

    protected function _validateLocalPrimaryIndex(axis\ISchemaBasedStorageUnit $localUnit, ISchema $localSchema) {
        if(!$localPrimaryIndex = $localSchema->getPrimaryIndex()) {
            throw new RuntimeException(
                'Relation table '.$localUnit->getUnitId().' does not have a primary index'
            );
        }

        return $localPrimaryIndex;
    }

    protected function _validateTargetPrimaryIndex(axis\ISchemaBasedStorageUnit $targetUnit, ISchema $targetSchema=null) {
        if($targetSchema === null) {
            $targetSchema = $targetUnit->getTransientUnitSchema();
        }

        if(!$targetPrimaryIndex = $targetSchema->getPrimaryIndex()) {
            throw new RuntimeException(
                'Relation unit '.$targetUnit->getUnitId().' does not have a primary index'
            );
        }

        return $targetPrimaryIndex;
    }

    protected function _validateDefaultValue(axis\ISchemaBasedStorageUnit $localUnit, array $targetPrimaryFields) {
        if($this->_defaultValue === null) {
            return;
        }

        if($this instanceof IOneRelationField) {
            if(!is_array($this->_defaultValue)) {
                if(count($targetPrimaryFields) > 1) {
                    throw new axis\schema\RuntimeException(
                        'Default value for a multi key relation must be a keyed array'
                    );
                }
                
                $this->_defaultValue = array($targetPrimaryFields[0] => $this->_defaultValue);
            }
            
            foreach($targetPrimaryFields as $field) {
                if(!array_key_exists($field, $this->_defaultValue)) {
                    throw new axis\schema\RuntimeException(
                        'Default value for a multi key relation must contain values for all target primary keys'
                    );
                }
            }
        } else if($this instanceof IManyRelationField) {
            // TODO: validate default value
        }
    }



    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        if($this instanceof INullPrimitiveField) {
            return new opal\schema\Primitive_Null($this);
        }

        $targetUnit = axis\Model::loadUnitFromId($this->_targetUnitId, $unit->getApplication());
        $targetSchema = $targetUnit->getTransientUnitSchema();
        $targetPrimaryIndex = $targetSchema->getPrimaryIndex();

        $primitives = array();

        foreach($targetPrimaryIndex->getFields() as $name => $field) {
            $primitive = $field->toPrimitive($targetUnit, $targetSchema)
                ->isNullable(true);

            if($field instanceof axis\schema\IMultiPrimitiveField) {
                $name = $primitive->getName();
            }

            $primitive->_setName($this->_getSubPrimitiveName($name));

            if($this->_defaultValue !== null) {
                $primitive->setDefaultValue($this->_defaultValue[$name]);
            }
            
            if($primitive instanceof opal\schema\IAutoIncrementableField) {
                $primitive->shouldAutoIncrement(false);
            }
            
            $primitives[$name] = $primitive;
        }

        if(count($primitives) == 1) {
            return array_shift($primitives);
        }

        return new opal\schema\Primitive_MultiField($this, $primitives);
    }

    protected function _getSubPrimitiveName($name) {
        return $this->_name.'_'.$name;
    }

    protected function _setRelationStorageArray(array $data) {
        $this->_targetUnitId = $data['tui'];
    }

    protected function _getRelationStorageArray() {
        return [
            'tui' => $this->_targetUnitId
        ];
    }
}


interface IInverseRelationField extends IRelationField {
    public function setTargetField($field);
    public function getTargetField();
}


trait TInverseRelationField {

    protected $_targetField;

    public function setTargetField($field) {
        if($field != $this->_targetField) {
            $this->_hasChanged = true;
        }
        
        $this->_targetField = $field;
        return $this;
    }
    
    public function getTargetField() {
        return $this->_targetField;
    }

    protected function _validateInverseRelationField(axis\ISchemaBasedStorageUnit $targetUnit, ISchema $targetSchema=null) {
        if($targetSchema === null) {
            $targetSchema = $targetUnit->getTransientUnitSchema();
        }

        if(!$targetField = $targetSchema->getField($this->_targetField)) {
            throw new RuntimeException(
                'Target field '.$this->_targetField.' could not be found in '.$targetUnit->getUnitId()
            );
        }

        if($this instanceof IOneChildField) {
            if(!$targetField instanceof IOneParentField) {
                throw new RuntimeException(
                    'Target field '.$this->_targetField.' is not a OneParent field'
                );
            }
        } else if($this instanceof IOneParentField) {
            if(!$targetField instanceof IOneChildField) {
                throw new RuntimeException(
                    'Target field '.$this->_targetField.' is not a OneChild field'
                );
            }
        } else if($this instanceof IOneToManyField) {
            if(!$targetField instanceof IManyToOneField) {
                throw new RuntimeException(
                    'Target field '.$this->_targetField.' is not a ManyToOne field'
                );
            }
        } else if($this instanceof IManyToOneField) {
            if(!$targetField instanceof IOneToManyField) {
                throw new RuntimeException(
                    'Target field '.$this->_targetField.' is not a OneToMany field'
                );
            }
        } else if($this instanceof IManyToManyField) {
            if(!$targetField instanceof IManyToManyField) {
                throw new RuntimeException(
                    'Target field '.$this->_targetField.' is not a ManyToMany field'
                );
            }
        }

        return $targetField;
    }

    protected function _setInverseRelationStorageArray(array $data) {
        $this->_targetField = $data['tfl'];
    }

    protected function _getInverseRelationStorageArray() {
        return [
            'tfl' => $this->_targetField
        ];
    }
}




interface IOneRelationField extends IRelationField {}
interface IManyRelationField extends IRelationField, INullPrimitiveField {}

interface IBridgedRelationField extends IRelationField {
    public function setBridgeUnitId($id);
    public function getBridgeUnitId();
    
    public function getBridgeUnit(core\IApplication $application=null);
    public function getBridgeTargetFieldName();
    public function isSelfReference();
    
    public function getLocalPrimaryFieldNames();
    public function getTargetPrimaryFieldNames();
}


trait TBridgedRelationField {

    protected $_bridgeUnitId;
    protected $_bridgeLocalFieldName;
    protected $_bridgeTargetFieldName;

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
        return axis\Model::loadUnitFromId($this->_bridgeUnitId, $application);
    }

    public function getBridgeLocalFieldName() {
        return $this->_bridgeLocalFieldName;
    }

    public function getBridgeTargetFieldName() {
        return $this->_bridgeTargetFieldName;
    }

    public function isSelfReference() {
        return $this->_bridgeLocalFieldName == substr($this->_bridgeTargetFieldName, 0, -3)
            || $this->_bridgeTargetFieldName == substr($this->_bridgeLocalFieldName, 0, -3);
    }

    protected function _sanitizeBridgeUnitId(axis\ISchemaBasedStorageUnit $localUnit) {
        $targetUnit = $this->getTargetUnit($localUnit->getApplication());
        $modelName = $localUnit->getModel()->getModelName();

        $this->_bridgeLocalFieldName = $localUnit->getUnitName();
        $this->_bridgeTargetFieldName = $targetUnit->getUnitName();

        if($this instanceof IManyToManyField) {
            if($this->isDominant() && empty($this->_bridgeUnitId)) {
                $this->_bridgeUnitId = $modelName.axis\IUnit::ID_SEPARATOR.$this->_getBridgeUnitType().'('.$localUnit->getUnitName().'.'.$this->_name.')';
            }
            
            if(!empty($this->_bridgeUnitId) && false === strpos($this->_bridgeUnitId, axis\IUnit::ID_SEPARATOR)) {
                $this->_bridgeUnitId = $modelName.axis\IUnit::ID_SEPARATOR.$this->_bridgeUnitId;
            }

            if($this->_bridgeTargetFieldName == $localUnit->getUnitName()) {
                if($this->isDominant()) {
                    $this->_bridgeTargetFieldName .= 'Ref';
                } else {
                    $this->_bridgeLocalFieldName .= 'Ref';
                }
            }
        } else {
            if(empty($this->_bridgeUnitId)) {
                $this->_bridgeUnitId = $modelName.axis\IUnit::ID_SEPARATOR.$this->_getBridgeUnitType().'('.$localUnit->getUnitName().'.'.$this->_name.')';
            }
            
            if(false === strpos($this->_bridgeUnitId, axis\IUnit::ID_SEPARATOR)) {
                $this->_bridgeUnitId = $modelName.axis\IUnit::ID_SEPARATOR.$this->_bridgeUnitId;
            }

            if($this->_bridgeTargetFieldName == $localUnit->getUnitName()) {
                $this->_bridgeTargetFieldName .= 'Ref';
            }
        }
    }

    protected function _validateBridgeUnit(axis\ISchemaBasedStorageUnit $localUnit) {
        $bridgeUnit = axis\Model::loadUnitFromId($this->_bridgeUnitId, $localUnit->getApplication());

        if($bridgeUnit->getModel()->getModelName() != $localUnit->getModel()->getModelName()) {
            throw new RuntimeException(
                'Bridge units must be local to the dominant participant - '.
                $this->_bridgeUnitId.' should be on model '.$localUnit->getModel()->getModelName()
            );
        }

        return $bridgeUnit;
    }

    protected function _getBridgeUnitType() {
        return 'table.ManyBridge';
    }

    protected function _setBridgeRelationStorageArray(array $data) {
        $this->_bridgeUnitId = $data['bui'];
        $this->_bridgeLocalFieldName = $data['blf'];
        $this->_bridgeTargetFieldName = $data['btf'];
    }

    protected function _getBridgeRelationStorageArray() {
        return [
            'bui' => $this->_bridgeUnitId,
            'blf' => $this->_bridgeLocalFieldName,
            'btf' => $this->_bridgeTargetFieldName
        ];
    }
}


interface IOneField extends IOneRelationField, IMultiPrimitiveField {}
interface IOneParentField extends IOneRelationField, IMultiPrimitiveField {}
interface IOneChildField extends IOneRelationField, INullPrimitiveField {}
interface IManyToOneField extends IOneRelationField, IMultiPrimitiveField, IInverseRelationField {}

interface IManyField extends IManyRelationField, IBridgedRelationField {}

interface IManyToManyField extends IManyRelationField, IBridgedRelationField, IInverseRelationField {
    public function isDominant($flag=null);
}

interface IOneToManyField extends IManyRelationField, IInverseRelationField {}






// Bridge
interface IBridge {
    public function getUnit();
    public function getAxisSchema();
    public function getTargetSchema();
    public function updateTargetSchema();
}
