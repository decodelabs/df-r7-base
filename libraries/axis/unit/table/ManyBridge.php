<?php

namespace df\axis\unit\table;

use df\core;
use df\axis;

class ManyBridge extends Base implements axis\IVirtualUnit {
    
    protected $_dominantUnitName;
    protected $_dominantFieldName;
    private $_isVirtual = false;
    
    public static function loadVirtual(axis\IModel $model, array $args) {
        $fieldId = array_shift($args);
        $parts = explode('.', $fieldId);
        $unitName = array_shift($parts);
        $fieldName = array_shift($parts);
        
        $output = new self($model);
        $output->_dominantUnitName = $unitName;
        $output->_dominantFieldName = $fieldName;
        $output->_isVirtual = true;
        
        return $output;
    }
    
    public function getCanonicalUnitName() {
        if($this->_isVirtual) {
            return 'tblMB_'.$this->_dominantUnitName.'_'.$this->_dominantFieldName;
        } else {
            return parent::getCanonicalUnitName();
        }
    }
    
    protected function _buildInitialSchema() {
        $dominantUnit = $this->_model->getUnit($this->_dominantUnitName);
        $dominantSchema = $dominantUnit->getTransientUnitSchema();
        $dominantField = $dominantSchema->getField($this->_dominantFieldName);
        $dominantFieldPrefix = $this->getDominantFieldPrefix();
        
        if(!$dominantField) {
            throw new axis\schema\FieldTypeNotFoundException(
                'Target Many relation field could not be found on unit '.$dominantUnit->getUnitId()
            );
        }
        
        
        $submissiveUnit = axis\Unit::fromId($dominantField->getTargetUnitId(), $this->getApplication());
        $submissiveSchema = $submissiveUnit->getTransientUnitSchema();
        $submissiveFieldPrefix = $this->getSubmissiveFieldPrefix($submissiveUnit);
        
        $dominantPrimaryFields = $dominantField->getLocalPrimaryFieldNames();
        $submissivePrimaryFields = $dominantField->getTargetPrimaryFieldNames();
        
        
        $schema = new axis\schema\Base($this, $this->getUnitName());
        $bridgePrimaryFields = array();
        
        foreach($dominantPrimaryFields as $fieldName) {
            if(!$field = $dominantSchema->getField($fieldName)) {
                throw new axis\schema\FieldTypeNotFoundException(
                    'Many bridge dominant primary field '.$fieldName.' could not be found in schema '.$dominantUnit->getUnitId()
                );
            }
            
            $bridgePrimaryFields[] = $bridgeFieldName = $dominantFieldPrefix.$fieldName;
            $bridgeField = $field->duplicateForRelation($dominantUnit, $dominantSchema)->_setName($bridgeFieldName);
            
            $schema->addPreparedField($bridgeField);
        }
        
        foreach($submissivePrimaryFields as $fieldName) {
            if(!$field = $submissiveSchema->getField($fieldName)) {
                throw new axis\schema\FieldTypeNotFoundException(
                    'Many bridge submissive primary field '.$fieldName.' could not be found in schema '.$submissiveUnit->getUnitId()
                );
            }
            
            $bridgePrimaryFields[] = $bridgeFieldName = $submissiveFieldPrefix.$fieldName;
            $bridgeField = $field->duplicateForRelation($submissiveUnit, $submissiveSchema)->_setName($bridgeFieldName);
            
            $schema->addPreparedField($bridgeField);
        }
        
        $schema->addPrimaryIndex('primary', $bridgePrimaryFields);
        
        return $schema;
    }
    
    public function getDominantUnitName() {
        return $this->_dominantUnitName;
    }
    
    public function getDominantFieldName() {
        return $this->_dominantFieldName;
    }
    
    public function getDominantFieldPrefix() {
        return $this->_dominantUnitName.'_'.$this->_dominantFieldName.'_';
    }
    
    public function getSubmissiveFieldPrefix(axis\unit\table\Base $table) {
        return $table->getUnitName().'_';
    }
    
    public function getFieldPrefix(axis\unit\table\Base $table, $isDominant) {
        if($isDominant) {
            return $this->getDominantFieldPrefix();
        } else {
            return $this->getSubmissiveFieldPrefix($table);
        }
    }
    
    protected function _onCreate(axis\schema\ISchema $schema) {}
}
