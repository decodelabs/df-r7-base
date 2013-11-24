<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\schema;

use df;
use df\core;
use df\opal;
    
class RelationManifest implements IRelationManifest, core\IDumpable {

    protected $_fields = [];
    protected $_primitiveNames = [];

    public function __construct(opal\schema\IIndex $index) {
        foreach($index->getFields() as $name => $field) {
            if($field instanceof opal\schema\ITargetPrimaryFieldAwareRelationField) {
                $this->_fields[$name] = $field->getTargetRelationManifest();

                foreach($field->getPrimitiveFieldNames() as $subField) {
                    $this->_primitiveNames[] = $subField;
                }
            } else if($field instanceof opal\schema\IMultiPrimitiveField) {
                $this->_fields[$name] = $field->getPrimitiveFieldNames();

                foreach($this->_fields[$name] as $subField) {
                    $this->_primitiveNames[] = $subField;
                }
            } else {
                $this->_fields[$name] = $this->_primitiveNames[] = $name;
            }
        }
    }

    public function getPrimitiveFieldNames($prefix=null) {
        if($prefix !== null) {
            $output = [];
            $prefix = rtrim($prefix, '_').'_';

            foreach($this->_primitiveNames as $name) {
                $output[] = $prefix.$name;
            }

            return $output;
        }

        return $this->_primitiveNames;
    }

    public function isSingleField() {
        return count($this->_primitiveNames) == 1;
    }

    public function getSingleFieldName() {
        return $this->_primitiveNames[0];
    }

    public function validateValue($value) {
        $primitiveFields = $this->getPrimitiveFieldNames();
        $fieldCount = count($primitiveFields);

        if(is_scalar($value)) {
            return $fieldCount == 1;
        }

        core\dump($value);

        return true;
    }

    public function extractFromRow($key, array $row) {
        if(count($this->_primitiveNames) == 1) {
            if(isset($row[$key])) {
                return $row[$key];
            }

            $key = $key.'_'.$this->_primitiveNames[0];

            if(isset($row[$key])) {
                return $row[$key];
            }

            return null;
        } else {
            $output = [];

            foreach($this->_primitiveNames as $name) {
                $testKey = $key.'_'.$name;

                if(isset($row[$testKey])) {
                    $output[$name] = $row[$testKey];
                }
            }

            return $output;
        }
    }

    public function getIterator() {
        return new \ArrayIterator($this->_fields);
    }

    public function toArray() {
        return $this->_fields;
    }


    public function toPrimaryKeySet($value=null) {
        $values = [];

        foreach($this->_fields as $name => $field) {
            if($field instanceof IRelationManifest) {
                $values[$name] = $field->toPrimaryKeySet();
            } else if(is_array($field)) {
                foreach($field as $subField) {
                    $values[$subField] = null;
                }
            } else {
                $values[$name] = null;
            }
        }

        $output = new opal\record\PrimaryKeySet(array_keys($values), $values);

        if($value !== null) {
            $output->updateWith($value);
        }

        return $output;
    }


// Dump
    public function getDumpProperties() {
        return $this->_fields;
    }
}