<?php

/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\variant\mysql;

use df\opal;

class QueryExecutor extends opal\rdbms\QueryExecutor
{
    // Truncate
    public function truncate($tableName)
    {
        $sql = 'TRUNCATE TABLE ' . $this->_adapter->quoteIdentifier($tableName);
        $this->_adapter->prepare($sql)->executeRaw();

        return $this;
    }


// Limit
    public function defineLimit($limit, $offset = null)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;

        if ($offset <= 0) {
            $offset = 0;
        }

        if ($offset > 0 && $limit == 0) {
            $limit = '18446744073709551615';
        }

        if ($limit > 0) {
            if ($offset > 0) {
                return 'LIMIT ' . $offset . ', ' . $limit;
            } else {
                return 'LIMIT ' . $limit;
            }
        }
    }
}
