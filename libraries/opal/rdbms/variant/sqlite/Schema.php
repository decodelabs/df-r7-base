<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\sqlite;

use df;
use df\core;
use df\opal;

class Schema extends opal\rdbms\schema\Base implements ISchema {
    
// Constraints
    protected function _createTrigger($name, $event, $timing, $statement) {
        return new Trigger($this, $name, $event, $timing, $statement);
    }
}
