<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\sqlite;

use df;
use df\core;
use df\opal;
    
class Database extends opal\rdbms\Database {

    public function getTableList() {
        $stmt = $this->_adapter->prepare('SELECT name FROM sqlite_master WHERE type=:a;');
        $stmt->bind(':a', 'table');
        $res = $stmt->executeRead();
        $output = array();

        foreach($res as $row) {
            $output[] = $row['name'];
        }
        
        return $output;
    }
}