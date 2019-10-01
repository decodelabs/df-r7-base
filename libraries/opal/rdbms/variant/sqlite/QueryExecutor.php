<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\sqlite;

use df;
use df\core;
use df\opal;

class QueryExecutor extends opal\rdbms\QueryExecutor
{

// Truncate
    public function truncate($tableName)
    {
        $sql = 'DELETE FROM '.$this->_adapter->quoteIdentifier($tableName);
        $this->_adapter->executeSql($sql);
        $this->_adapter->executeSql('VACUUM');

        return $this;
    }


    // Insert
    public function executeInsertQuery($tableName)
    {
        if ($this->_query->shouldReplace()) {
            core\stub($tableName);
        }

        $this->_stmt->appendSql('INSERT');

        if ($this->_query->ifNotExists()) {
            $this->_stmt->appendSql(' OR IGNORE');
        }

        $this->_stmt->appendSql(' INTO '.$this->_adapter->quoteIdentifier($tableName));


        $fields = [];
        $values = [];

        foreach ($this->_query->getRow() as $field => $value) {
            $fields[] = $this->_adapter->quoteIdentifier($field);
            $values[] = ':'.$field;

            if (is_array($value)) {
                if (count($value) == 1) {
                    // This is a total hack - you need to trace the real problem with multi-value keys
                    $value = array_shift($value);
                } else {
                    dd($value, $field, 'You need to trace back multi key primary field retention');
                }
            }

            $this->_stmt->bind($field, $value);
        }

        $this->_stmt->appendSql(' ('.implode(',', $fields).') VALUES ('.implode(',', $values).')');
        $this->_stmt->executeWrite();

        return $this->_adapter->getLastInsertId();
    }


    // Batch insert
    public function executeBatchInsertQuery($tableName)
    {
        if ($this->_query->shouldReplace()) {
            core\stub($tableName);
        }

        $this->_stmt->appendSql('INSERT');

        if ($this->_query->ifNotExists()) {
            $this->_stmt->appendSql(' OR IGNORE');
        }

        $this->_stmt->appendSql(' INTO '.$this->_adapter->quoteIdentifier($tableName));

        $fields = $bindValues = $this->_query->getFields();

        foreach ($fields as $i => $field) {
            $fields[$i] = $this->_adapter->quoteIdentifier($field);
        }

        $this->_stmt->appendSql(' ('.implode(',', $fields).') VALUES ');

        foreach ($bindValues as &$field) {
            $field = ':'.$field;
        }

        $this->_stmt->appendSql('('.implode(',', $bindValues).')');

        $rows = [];
        $output = 0;

        foreach ($this->_query->getRows() as $row) {
            foreach ($row as $key => $value) {
                $this->_stmt->bind($key, $value);
            }

            $output += $this->_stmt->executeWrite();
            $this->_stmt->reset();
        }

        return $output;
    }

    // Limit
    public function defineLimit($limit, $offset=null)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        if ($offset < 0) {
            $offset = 0;
        }

        if ($offset > 0 && $limit == 0) {
            $limit = '18446744073709551615';
        }


        if ($limit > 0) {
            $output = 'LIMIT '.$limit;

            if ($offset > 0) {
                $output .= ' OFFSET '.$offset;
            }

            return $output;
        }
    }
}
