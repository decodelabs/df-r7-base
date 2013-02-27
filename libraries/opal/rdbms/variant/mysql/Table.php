<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\mysql;

use df;
use df\core;
use df\opal;

class Table extends opal\rdbms\Table {
    
    
// Truncate
    public function truncate() {
        $sql = 'TRUNCATE TABLE '.$this->_adapter->quoteIdentifier($this->_name);
        $this->_adapter->prepare($sql)->executeRaw();
        
        return $this;
    }
    
    
    
// Query limit
    protected function _defineQueryLimit($limit, $offset=null) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        if($offset <= 0) {
            $offset = 0;
        }
        
        if($offset > 0 && $limit == 0) {
            $limit = '18446744073709551615';
        }
        
        if($limit > 0) {
            if($offset > 0) {
                return 'LIMIT '.$offset.', '.$limit;
            } else {
                return 'LIMIT '.$limit;
            }
        }
    }
}
