<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\bridge;

use df;
use df\core;
use df\axis;
use df\opal;

class Rdbms extends Base {
    
    protected $_rdbmsAdapter;
    
    public function __construct(axis\ISchemaBasedStorageUnit $unit, opal\rdbms\IAdapter $rdbmsAdapter, axis\schema\ISchema $axisSchema, opal\schema\ISchema $targetSchema=null) {
        $this->_rdbmsAdapter = $rdbmsAdapter;
        parent::__construct($unit, $axisSchema);
    }
    
    public function getRdbmsAdapter() {
        return $this->_rdbmsAdapter;
    }
    
    protected function _createTargetSchema() {
        return $this->_rdbmsAdapter->newSchema($this->_unit->getStorageBackendName());
    }
    
    
// Binary
    protected function _createBinaryField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'binary', $primitive->getLength());
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Bit
    protected function _createBitField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'bit', $primitive->getBitSize());
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Blob
    protected function _createBlobField(opal\schema\IPrimitive $primitive) {
        switch($primitive->getExponentSize()) {
            case 8: $type = 'tinyblob'; break;
            case 16: $type = 'blob'; break;
            case 24: $type = 'mediumblob'; break;
            case 32: $type = 'longblob'; break;
        }
            
        $field = $this->_targetSchema->createField($primitive->getName(), $type);
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Boolean
    protected function _createBooleanField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'bool');
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Char
    protected function _createCharField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'char', $primitive->getLength())
            ->setCharacterSet($primitive->getCharacterSet());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Currency
    protected function _createCurrencyField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'decimal', 19, 4);
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Dataobject
    protected function _createDataObjectField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'blob');
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Date
    protected function _createDateField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'date');
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Datetime
    protected function _createDateTimeField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'datetime');
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Decimal
    protected function _createDecimalField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'decimal', $primitive->getPrecision(), $primitive->getScale());
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Enum
    protected function _createEnumField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'enum', $primitive->getOptions())
            ->setCharacterSet($primitive->getCharacterSet());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Float
    protected function _createFloatField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'float', $primitive->getPrecision(), $primitive->getScale());
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Guid
    protected function _createGuidField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'binary', 16);
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Integer
    protected function _createIntegerField(opal\schema\IPrimitive $primitive) {
        switch($primitive->getByteSize()) {
            case 1: $type = 'tinyint'; break;
            case 2: $type = 'smallint'; break;
            case 3: $type = 'mediumint'; break;
            case 4: $type = 'int'; break;
            case 8: $type = 'bigint'; break;
        }
        
        $field = $this->_targetSchema->createField($primitive->getName(), $type)
            ->isUnsigned($primitive->isUnsigned())
            ->shouldZerofill($primitive->shouldZerofill())
            ->shouldAutoIncrement($primitive->shouldAutoIncrement());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    


// Set
    protected function _createSetField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'set', $primitive->getOptions())
            ->setCharacterSet($primitive->getCharacterSet());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Text
    protected function _createTextField(opal\schema\IPrimitive $primitive) {
        switch($primitive->getExponentSize()) {
            case 8: $type = 'tinytext'; break;
            case 16: $type = 'text'; break;
            case 24: $type = 'mediumtext'; break;
            case 32: $type = 'longtext'; break;
        }
        
        $field = $this->_targetSchema->createField($primitive->getName(), $type)
            ->setCharacterSet($primitive->getCharacterSet());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Time
    protected function _createTimeField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'time');
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }

// Timestamp
    protected function _createTimestampField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'timestamp')
            ->shouldTimestampAsDefault($primitive->shouldTimestampAsDefault())
            ->shouldTimestampOnUpdate($primitive->shouldTimestampOnUpdate());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        
        if($primitive->shouldTimestampAsDefault()) {
            $field->setDefaultValue(null);
        }
        
        return $field;
    }

// Varbinary
    protected function _createVarbinaryField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'varbinary', $primitive->getLength());
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Varchar
    protected function _createVarcharField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'varchar', $primitive->getLength())
            ->setCharacterSet($primitive->getCharacterSet());
            
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
// Year
    protected function _createYearField(opal\schema\IPrimitive $primitive) {
        $field = $this->_targetSchema->createField($primitive->getName(), 'smallint');
        $this->_importBasePrimitiveOptions($field, $primitive);
        return $field;
    }
    
    
    
// Base options
    protected function _importBasePrimitiveOptions(opal\rdbms\schema\IField $field, opal\schema\IPrimitive $primitive) {
        $field->isNullable($primitive->isNullable())
            ->setDefaultValue($primitive->getDefaultValue())
            ->setComment($primitive->getComment());
    }
    
    
    
    
// Indexes
    protected function _getIndexName(opal\schema\IIndex $axisIndex, $isPrimary) {
        if($isPrimary && $this->_rdbmsAdapter->getServerType() == 'mysql') {
            return 'PRIMARY';
        } 
        
        return 'idx_'.$axisIndex->getName();
    }
}
