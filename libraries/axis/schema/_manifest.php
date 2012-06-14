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



// Interfaces
interface ISchema extends opal\schema\ISchema, opal\schema\IFieldProvider, opal\schema\IIndexProvider {
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
    public function duplicateForRelation(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema);
    
    public function sanitize(axis\ISchemaBasedStorageUnit $unit, ISchema $schema);
    public function validate(axis\ISchemaBasedStorageUnit $unit, ISchema $schema);
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, ISchema $schema);
}


interface IAutoIndexField extends IField {}
interface IAutoUniqueField extends IAutoIndexField {}
interface IAutoPrimaryField extends IAutoUniqueField {}


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
    protected function _getLengthRestrictedStorageArray() {
        return [
            'lng' => $this->_length,
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

interface IRelationField extends IField {
    public function setTargetUnitId($targetUnitId);
    public function getTargetUnitId();
}

interface IInverseRelationField extends IRelationField {
    public function setTargetField($field);
    public function getTargetField();
}


interface IQueryClauseRewriterField extends IField {
    public function rewriteVirtualQueryClause(opal\query\IClauseFactory $parent, opal\query\IVirtualField $field, $operator, $value, $isOr=false);
}




// Bridge
interface IBridge {
    public function getUnit();
    public function getAxisSchema();
    public function getTargetSchema();
    public function updateTargetSchema();
}
