<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df;
use df\core;
use df\axis;
use df\opal;
    
class Slug extends Base implements axis\schema\IAutoUniqueField {

    protected $_allowPathFormat = false;

    public function allowPathFormat($flag=null) {
        if($flag !== null) {
            if((bool)$flag != $this->_allowPathFormat) {
                $this->_hasChanged = true;
            }

            $this->_allowPathFormat = (bool)$flag;
            return $this;    
        }    
        
        return $this->_allowPathFormat;
    }


// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_Varchar($this, 255);
    }


// Values
    public function sanitizeValue($value, $forRecord) {
        if($value === null && $this->isNullable()) {
            return null;
        }

        if($this->_allowPathFormat) {
            return core\string\Manipulator::formatPathSlug($value);
        } else {
            return core\string\Manipulator::formatSlug($value);
        }
    }



// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);

        $this->_allowPathFormat = $data['apf'];
    }

    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            [
                'apf' => $this->_allowPathFormat
            ]
        );
    }
}