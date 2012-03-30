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

class Boolean extends Base {
    
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        //return new opal\schema\Primitive_Bit($this, 1);
        return new opal\schema\Primitive_Boolean($this);
    }
}
