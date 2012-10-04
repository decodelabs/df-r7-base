<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\mysql;

use df;
use df\core;
use df\opal;
    
class Database extends opal\rdbms\Database {

    public function getTableList() {
        $stmt = $this->_adapter->prepare('SHOW TABLES');
        $res = $stmt->executeRead();
        $key = 'Tables_in_'.$this->getName();
        $output = array();

        foreach($res as $row) {
            $output[] = $row[$key];
        }
        
        return $output;
    }
}