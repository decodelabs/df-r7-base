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
interface IManager extends core\IManager {
    public function fetchFor(axis\ISchemaBasedStorageUnit $unit, $transient=false);
    public function store(axis\ISchemaBasedStorageUnit $unit, ISchema $schema);
    public function getTimestampFor(axis\ISchemaBasedStorageUnit $unit);
    public function insert(axis\ISchemaBasedStorageUnit $unit, $jsonData, $version);
    public function update(axis\ISchemaBasedStorageUnit $unit, $jsonData, $version);
    public function remove(axis\ISchemaBasedStorageUnit $unit);
    public function removeId($unitId);
    public function clearCache(axis\ISchemaBasedStorageUnit $unit=null);
    public function fetchStoredUnitList();
    public function markTransient(axis\ISchemaBasedStorageUnit $unit);
    public function unmarkTransient(axis\ISchemaBasedStorageUnit $unit);
    public function getSchemaUnit();
}



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
    
    public function sanitizeClauseValue($value);
    public function duplicateForRelation(axis\ISchemaBasedStorageUnit $unit, ISchema $schema);
    
    public function sanitize(axis\ISchemaBasedStorageUnit $unit, ISchema $schema);
    public function validate(axis\ISchemaBasedStorageUnit $unit, ISchema $schema);
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, ISchema $schema);
    public function getReplacedPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema);
}


interface IAutoIndexField extends IField {
    public function shouldBeIndexed($flag=null);
}

trait TAutoIndexField {

    protected $_autoIndex = true;

    public function shouldBeIndexed($flag=null) {
        if($flag !== null) {
            $flag = (bool)$flag;
            
            if($flag !== $this->_autoIndex) {
                $this->_hasChanged = true;
            }
            
            $this->_autoIndex = $flag;
            return $this;
        }
        
        return $this->_autoIndex;
    }

// Ext. serialize
    protected function _setAutoIndexStorageArray(array $data) {
        $this->_autoIndex = isset($data['aui']) ? (bool)$data['aui'] : true;
    }

    protected function _getAutoIndexStorageArray() {
        return [
            'aui' => $this->_autoIndex
        ];
    }
}

interface IAutoUniqueField extends IAutoIndexField {
    public function shouldBeUnique($flag=null);
}

trait TAutoUniqueField {

    use TAutoIndexField;

    protected $_autoUnique = true;

    public function shouldBeUnique($flag=null) {
        if($flag !== null) {
            $flag = (bool)$flag;
            
            if($flag !== $this->_autoUnique) {
                $this->_hasChanged = true;
            }
            
            $this->_autoUnique = $flag;
            return $this;
        }
        
        return $this->_autoUnique;
    }

// Ext. serialize
    protected function _setAutoUniqueStorageArray(array $data) {
        $this->_autoUnique = isset($data['auu']) ? (bool)$data['auu'] : true;
        $this->_setAutoIndexStorageArray($data);
    }

    protected function _getAutoUniqueStorageArray() {
        return array_merge(
            ['auu' => $this->_autoUnique],
            $this->_getAutoIndexStorageArray()
        );
    }
}

interface IAutoPrimaryField extends IAutoUniqueField {
    public function shouldBePrimary($flag=null);
}

trait TAutoPrimaryField {

    use TAutoUniqueField;

    protected $_autoPrimary = true;

    public function shouldBePrimary($flag=null) {
        if($flag !== null) {
            $flag = (bool)$flag;
            
            if($flag !== $this->_autoPrimary) {
                $this->_hasChanged = true;
            }
            
            $this->_autoPrimary = $flag;
            return $this;
        }
        
        return $this->_autoPrimary;
    }

// Ext. serialize
    protected function _setAutoPrimaryStorageArray(array $data) {
        $this->_autoPrimary = isset($data['aup']) ? (bool)$data['aup'] : true;
        $this->_setAutoUniqueStorageArray($data);
    }

    protected function _getAutoPrimaryStorageArray() {
        return array_merge(
            ['aup' => $this->_autoPrimary],
            $this->_getAutoUniqueStorageArray()
        );
    }
}


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



interface IRelationField extends IField, opal\schema\IRelationField, opal\schema\IQueryClauseRewriterField {
    public function setTargetUnitId($targetUnitId);
    public function getTargetUnitId();
    public function getTargetUnit($clusterId=null);

    //public function shouldCascadeDelete($flag=null);
}


trait TRelationField {

    protected $_targetUnitId;
    protected $_globalCluster = false;
    //protected $_deleteCascade = false;

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

    public function getTargetUnit($clusterId=null) {
        return axis\Model::loadUnitFromId($this->_targetUnitId, $clusterId);
    }

    public function getTargetQueryAdapter($clusterId=null) {
        return axis\Model::loadUnitFromId($this->_targetUnitId, $clusterId);
    }

    public function isOnGlobalCluster($flag=null) {
        if($flag !== null) {
            $t = $this->_globalCluster;
            $this->_globalCluster = (bool)$flag;

            if($t != $this->_globalCluster) {
                $this->_hasChanged = true;
            }

            return $this;
        }

        return $this->_globalCluster;
    }

/*
    public function shouldCascadeDelete($flag=null) {
        if($flag !== null) {
            $t = $this->_deleteCascade;
            $this->_deleteCascade = (bool)$flag;

            if($t != $this->_deleteCascade) {
                $this->_hasChanged = true;
            }

            return $this;
        }

        return $this->_deleteCascade;
    }
*/


    public function canReturnNull() {
        return true;
    }


    public function rewritePopulateQueryToAttachment(opal\query\IPopulateQuery $populate) {
        return null;
    }


    protected function _sanitizeTargetUnitId(axis\ISchemaBasedStorageUnit $unit) {
        $model = $unit->getModel();
        
        if(false === strpos($this->_targetUnitId, '/')) {
            $this->_targetUnitId = $model->getModelName().'/'.$this->_targetUnitId;
        }
    }

    protected function _validateTargetUnit(axis\ISchemaBasedStorageUnit $localUnit) {
        $targetUnit = axis\Model::loadUnitFromId($this->_targetUnitId);
        
        if($targetUnit->getUnitType() != $localUnit->getUnitType()) {
            throw new RuntimeException(
                'Relation target unit '.$targetUnit->getUnitId().' does not match local unit '.$localUnit->getUnitId().' type ('.$localUnit->getUnitType().')'
            );
        }

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

        $this->_targetRelationManifest = new opal\schema\RelationManifest($targetPrimaryIndex);
        return $targetPrimaryIndex;
    }

    protected function _validateDefaultValue(axis\ISchemaBasedStorageUnit $localUnit) {
        if($this->_defaultValue === null) {
            return;
        }

        if($this instanceof opal\schema\IOneRelationField) {
            $targetRelationManifest = $this->getTargetRelationManifest();

            if(!$targetRelationManifest->validateValue($this->_defaultValue)) {
                throw new axis\schema\RuntimeException(
                    'Default value for relation field does not fit relation manifest'
                );
            }
        } else if($this instanceof opal\schema\IManyRelationField) {
            // TODO: validate default value
        }
    }



    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return $this->_toPrimitive($unit, $schema, false);
    }

    public function getReplacedPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return $this->_toPrimitive($unit, $schema, true);
    }

    private function _toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema, $replaced=false) {
        if($this instanceof opal\schema\INullPrimitiveField) {
            return new opal\schema\Primitive_Null($this);
        }
        
        $targetUnit = $this->getTargetUnit();
        $targetSchema = $targetUnit->getTransientUnitSchema(true);
        $targetPrimaryIndex = $targetSchema->getPrimaryIndex();

        $primitives = [];

        foreach($targetPrimaryIndex->getFields() as $name => $field) {
            if($replaced) {
                $oldName = $targetSchema->getOriginalFieldNameFor($name);
                $replacedField = $targetSchema->getReplacedField($oldName);

                if($replacedField) {
                    $field = $replacedField;
                    $name = $oldName;
                }
            }

            $primitive = $field->toPrimitive($targetUnit, $targetSchema)
                ->isNullable(true);

            if($field instanceof opal\schema\IMultiPrimitiveField) {
                $name = $primitive->getName();
            }

            $primitiveName = $this->_getSubPrimitiveName($name);
            $primitive->_setName($primitiveName);

            if($this->_defaultValue !== null) {
                $primitive->setDefaultValue($this->_defaultValue[$name]);
            }
            
            if($primitive instanceof opal\schema\IAutoIncrementableField) {
                $primitive->shouldAutoIncrement(false);
            }
            
            $primitives[$primitiveName] = $primitive;
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

        if(isset($data['gcl'])) {
            $this->_globalCluster = (bool)$data['gcl'];
        } else {
            $this->_globalCluster = false;
        }

        /*
        if(isset($data['odc'])) {
            $this->_deleteCascade = (bool)$data['odc'];
        } else {
            $this->_deleteCascade = false;
        }
        */
    }

    protected function _getRelationStorageArray() {
        return [
            'tui' => $this->_targetUnitId,
            'gcl' => $this->_globalCluster,
            //'odc' => $this->_deleteCascade
        ];
    }
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

trait TTargetPrimaryFieldAwareRelationField {

    use opal\schema\TField_TargetPrimaryFieldAwareRelation;

    public function getTargetPrimaryIndex() {
        return $this->getTargetUnit()->getTransientUnitSchema()->getPrimaryIndex();
    }
}


interface IBridgedRelationField extends IRelationField, opal\schema\IBridgedRelationField {
    public function setBridgeUnitId($id);
    public function getBridgeUnitId();
    
    public function getBridgeUnit($clusterId=null);
    public function isDominant($flag=null);
}


trait TBridgedRelationField {

    use TTargetPrimaryFieldAwareRelationField;
    use opal\schema\TField_BridgedRelation;

    protected $_bridgeUnitId;
    protected $_bridgeLocalFieldName;
    protected $_bridgeTargetFieldName;
    protected $_localRelationManifest;

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
    
    public function getBridgeUnit($clusterId=null) {
        return axis\Model::loadUnitFromId($this->_bridgeUnitId, $clusterId);
    }

    public function getBridgeQueryAdapter($clusterId=null) {
        return axis\Model::loadUnitFromId($this->_bridgeUnitId, $clusterId);
    }

    public function getBridgeLocalFieldName() {
        return $this->_bridgeLocalFieldName;
    }

    public function getBridgeTargetFieldName() {
        return $this->_bridgeTargetFieldName;
    }

    protected function _sanitizeBridgeUnitId(axis\ISchemaBasedStorageUnit $localUnit) {
        $targetUnit = $this->getTargetUnit();
        $modelName = $localUnit->getModel()->getModelName();

        $this->_bridgeLocalFieldName = $localUnit->getUnitName();
        $this->_bridgeTargetFieldName = $targetUnit->getUnitName();

        $isManyToMany = $this instanceof IManyToManyField;

        if(empty($this->_bridgeUnitId)) {
            if($isManyToMany && !$this->isDominant()) {
                //$buiArgs = [$targetUnit->getUnitName().'.'.$this->getTargetField()];
                $dominantField = $targetUnit->getTransientUnitSchema()->getField($this->getTargetField());
                $this->_bridgeUnitId = $dominantField->getBridgeUnitId();
            } else {
                $buiArgs = [$localUnit->getUnitName().'.'.$this->_name];
                $this->_bridgeUnitId = $modelName.'/'.$this->_getBridgeUnitType().'('.implode(',', $buiArgs).')';
            }
        }

        if(!empty($this->_bridgeUnitId) && false === strpos($this->_bridgeUnitId, '(')) {
            $parts = explode('/', $this->_bridgeUnitId, 2);
            $bridgeModelName = array_shift($parts);
            $bridgeId = array_shift($parts);

            if(!$bridgeId) {
                $bridgeId = $bridgeModelName;
                $bridgeModelName = $modelName;
            }

            $bridgeClass = axis\unit\table\Bridge::getBridgeClass($bridgeModelName, $bridgeId);

            if($bridgeClass::IS_SHARED) {
                if($isManyToMany && !$this->isDominant()) {
                    //$buiArgs = [$targetUnit->getUnitName().'.'.$this->getTargetField()];
                    $dominantField = $targetUnit->getTransientUnitSchema()->getField($this->getTargetField());
                    $this->_bridgeUnitId = $dominantField->getBridgeUnitId();
                } else {
                    $buiArgs = [$localUnit->getUnitName().'.'.$this->_name];
                    $buiArgs[] = $bridgeModelName.'/'.$bridgeId;
                    $this->_bridgeUnitId = $modelName.'/'.$this->_getBridgeUnitType().'('.implode(',', $buiArgs).')';
                }
            }
        }

        if(false === strpos($this->_bridgeUnitId, '/')) {
            $this->_bridgeUnitId = $modelName.'/'.$this->_bridgeUnitId;
        }

        if($this->_bridgeTargetFieldName == $localUnit->getUnitName()) {
            if($isManyToMany && !$this->isDominant()) {
                $this->_bridgeLocalFieldName .= opal\schema\IBridgedRelationField::SELF_REFERENCE_SUFFIX;
            } else {
                $this->_bridgeTargetFieldName .= opal\schema\IBridgedRelationField::SELF_REFERENCE_SUFFIX;
            }
        }
    }

    protected function _validateBridgeUnit(axis\ISchemaBasedStorageUnit $localUnit) {
        $bridgeUnit = axis\Model::loadUnitFromId($this->_bridgeUnitId);

        if($this instanceof IManyToManyField) {
            if($bridgeUnit->getModel()->getModelName() != $localUnit->getModel()->getModelName()) {
                throw new RuntimeException(
                    'Bridge units must be local to the dominant participant - '.
                    $this->_bridgeUnitId.' should be on model '.$localUnit->getModel()->getModelName()
                );
            }
        }

        return $bridgeUnit;
    }

    protected function _getBridgeUnitType() {
        return 'table.Bridge';
    }

    public function getLocalRelationManifest() {
        if(!$this->_localRelationManifest) {
            $schema = $this->getBridgeUnit()->getTransientUnitSchema();
            $this->_localRelationManifest = $schema->getField($this->_bridgeLocalFieldName)->getTargetRelationManifest();
        }

        return $this->_localRelationManifest;
    }
    

// Ext. serialize
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


interface IOneField extends IRelationField, opal\schema\IOneRelationField, opal\schema\IMultiPrimitiveField, opal\schema\ITargetPrimaryFieldAwareRelationField {}
interface IOneParentField extends IRelationField, opal\schema\IOneRelationField, opal\schema\IMultiPrimitiveField {}
interface IOneChildField extends IRelationField, opal\schema\IOneRelationField, opal\schema\INullPrimitiveField {}
interface IManyToOneField extends IRelationField, opal\schema\IOneRelationField, opal\schema\IMultiPrimitiveField, opal\schema\IInverseRelationField {}

interface IManyField extends IRelationField, opal\schema\IManyRelationField, IBridgedRelationField {}
interface IManyToManyField extends IRelationField, opal\schema\IManyRelationField, IBridgedRelationField, opal\schema\IInverseRelationField {}

interface IOneToManyField extends IRelationField, opal\schema\IManyRelationField, opal\schema\IInverseRelationField, opal\schema\ITargetPrimaryFieldAwareRelationField {}



// Bridge
interface ITranslator {
    public function getUnit();
    public function getAxisSchema();
    public function getTargetSchema();
    public function createFreshTargetSchema();
    public function updateTargetSchema();
}

class Cache extends core\cache\Base {}
