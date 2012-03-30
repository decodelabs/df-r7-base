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

class Base implements ISchema, core\IDumpable {
    
    use opal\schema\TSchema;
    use opal\schema\TSchema_FieldProvider;
    use opal\schema\TSchema_IndexProvider;
    
    protected $_version = 0;
    protected $_unitId;
    protected $_unitType;
    
    protected $_options = [
        'name' => null,
        'comment' => null
    ];
    
    public function __construct(axis\ISchemaBasedStorageUnit $unit, $name) {
        $this->_unitType = $unit->getUnitType();
        $this->setName($name);
    }
    
    public function getUnitType() {
        return $this->_unitType;
    }
    
    public function iterateVersion() {
        $this->_version++;
        return $this;
    }
    
    public function getVersion() {
        return $this->_version;
    }
    
    public function hasChanged() {
        return $this->_hasOptionChanges()
            || $this->_hasFieldChanges()
            || $this->_hasIndexChanges();
    }
    
    public function acceptChanges() {
        $this->_acceptOptionChanges();
        $this->_acceptFieldChanges();
        $this->_acceptIndexChanges();
        
        return $this;
    }
    
    protected function _createField($name, $type, array $args) {
        return axis\schema\field\Base::factory($this, $name, $type, $args);
    }
    
    protected function _createIndex($name, $fields=null) {
        return new axis\schema\constraint\Index($this, $name, $fields);
    }
    
    
    
// Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $unit) {
        foreach($this->_fields as $field) {
            if($field instanceof axis\schema\IField) {
                $field->sanitize($unit, $this);
            }
            
            if($field instanceof axis\schema\IAutoIndexField) {
                $index = $this->addIndex($field->getName(), $field);
                
                if($field instanceof axis\schema\IAutoUniqueField) {
                    $index->isUnique(true);
                }
                
                if($field instanceof axis\schema\IAutoPrimaryField) {
                    $this->setPrimaryIndex($index);
                }
            }
        }
        
        return $this;
    }
    
    public function validate(axis\ISchemaBasedStorageUnit $unit) {
        foreach($this->_fields as $field) {
            if($field instanceof axis\schema\IField) {
                $field->validate($unit, $this);
            }
        }
        
        return $this;
    }
    
    
    
// Fields
    public function getPrimitiveFieldNames() {
        $output = array();
        
        foreach($this->_fields as $field) {
            if($field instanceof axis\schema\IMultiPrimitiveField) {
                $names = $field->getPrimitiveFieldNames();
                
                if(!empty($names)) {
                    foreach($names as $name) {
                        $output[] = $name;
                    }
                }
            } else if($field instanceof axis\schema\INullPrimitiveField) {
                continue;
            } else {
                $output[] = $field->getName();
            }
        }
        
        return $output;
    }
}
