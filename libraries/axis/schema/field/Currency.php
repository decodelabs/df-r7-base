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
    
class Currency extends Base {

// Primitive
	public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
		return opal\schema\Primitive_Currency($this);
	}

// Ext. serialize
	protected function _importStorageArray(array $data) {
		$this->_setBaseStorageArray($data);
	}

	public function toStorageArray() {
		return $this->_getBaseStorageArray();
	}
}