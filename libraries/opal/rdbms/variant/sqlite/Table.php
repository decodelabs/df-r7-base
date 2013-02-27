<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\sqlite;

use df;
use df\core;
use df\opal;

class Table extends opal\rdbms\Table {
    
    
// Truncate
    public function truncate() {
        $sql = 'DELETE FROM '.$this->_adapter->quoteIdentifier($this->_name);
        $this->_adapter->executeSql($sql);
        $this->_adapter->executeSql('VACUUM');
        
        return $this;
    }
    
    
// Replace query
    public function executeReplaceQuery(opal\query\IReplaceQuery $query) {
        core\stub($query);
    }
    
// Batch replace query
    public function executeBatchReplaceQuery(opal\query\IBatchReplaceQuery $query) {
        core\stub($query);
    }
    
    
// Query limit
    protected function _defineQueryLimit($limit, $offset=null) {
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        if($offset < 0) {
            $offset = 0;
        }
        
        if($offset > 0 && $limit == 0) {
            $limit = '18446744073709551615';
        }
        
        
        if($limit > 0) {
            $output = 'LIMIT '.$limit;
            
            if($offset > 0) {
                $output .= ' OFFSET '.$offset;
            }
            
            return $output;
        }
    }
    
    
// Insert
    public function executeBatchInsertQuery(opal\query\IBatchInsertQuery $query) {
        $stmt = $this->_adapter->prepare(
            'INSERT INTO '.$this->_adapter->quoteIdentifier($this->_name)
        );
        
        $fields = $bindValues = $query->getFields();
        $stmt->appendSql(' ('.implode(',', $fields).') VALUES ');
        
        foreach($bindValues as &$field) {
            $field = ':'.$field;
        }
        
        $stmt->appendSql('('.implode(',', $bindValues).')');
        
        $rows = array();
        $output = 0;
        
        foreach($query->getRows() as $row) {
            foreach($row as $key => $value) {
                $stmt->bind($key, $value);
            }
            
            $output += $stmt->executeWrite();
        }
        
        return $output;
    }
}
