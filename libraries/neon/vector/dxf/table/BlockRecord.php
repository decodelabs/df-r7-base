<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\vector\dxf\table;

use df;
use df\core;
use df\neon;

class BlockRecord implements neon\vector\dxf\IBlockRecordTable {
    
    use neon\vector\dxf\TTable;

    public function getType() {
        return 'BLOCK_RECORD';
    }

    public function toString() {
        return $this->_writeBaseString();
    }
}