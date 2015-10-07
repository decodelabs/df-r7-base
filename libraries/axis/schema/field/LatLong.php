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

class LatLong extends Base {

    const PRECISION = 10;
    const SCALE = 6;


// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return new opal\schema\Primitive_Decimal($this, self::PRECISION, self::SCALE);
    }


// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
    }

    public function toStorageArray() {
        return $this->_getBaseStorageArray();
    }
}