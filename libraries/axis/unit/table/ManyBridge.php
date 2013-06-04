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
            return $this->_dominantUnitName.'_'.$this->_dominantFieldName;
        } else {
            return parent::getCanonicalUnitName();
        }
    }
    

    public function buildInitialSchema() {
        if(!$this->_dominantUnitName) {
            throw new axis\schema\LogicException(
                'ManyBridge "'.$this->getUnitName().'" does not have a dominant unit defined - are you sure ManyBridge is the unit type you want to use?'
            );
        }

        $dominantUnit = $this->_model->getUnit($this->_dominantUnitName);
        $dominantSchema = $dominantUnit->getTransientUnitSchema();
        $dominantField = $dominantSchema->getField($this->_dominantFieldName);
        
        if(!$dominantField) {
            throw new axis\schema\FieldTypeNotFoundException(
                'Target Many relation field could not be found on unit '.$dominantUnit->getUnitId()
            );
        }

        $submissiveUnit = axis\Model::loadUnitFromId($dominantField->getTargetUnitId(), $this->getApplication());
        $submissiveSchema = $submissiveUnit->getTransientUnitSchema();

        $schema = new axis\schema\Base($this, $this->getUnitName());

        $bridgePrimaryFields = [
            $dominantName = $dominantSchema->getName(), 
            $submissiveName = $dominantField->getBridgeTargetFieldName()
        ];

        $schema->addField($dominantName, 'KeyGroup', $dominantUnit->getUnitId());
        $schema->addField($submissiveName, 'KeyGroup', $submissiveUnit->getUnitId());

        $schema->addPrimaryIndex('primary', $bridgePrimaryFields);

        return $schema;
    }

    public function getDominantUnitName() {
        return $this->_dominantUnitName;
    }
    
    public function getDominantFieldName() {
        return $this->_dominantFieldName;
    }
    
    protected function _onCreate(axis\schema\ISchema $schema) {}


    public function getBridgeFieldNames($aliasPrefix=null) {
        $output = array();
        $i = 0;

        foreach($this->getUnitSchema()->getFields() as $name => $field) {
            $i++;

            if($i <= 2) {
                continue;
            }

            if($aliasPrefix !== null) {
                $name .= ' as '.$aliasPrefix.'.'.$name;
            }
            
            $output[] = $name;
        }

        return $output;
    }
}
