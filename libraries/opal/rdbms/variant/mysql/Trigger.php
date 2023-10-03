<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\variant\mysql;

use df\opal;

class Trigger extends opal\rdbms\schema\constraint\Trigger
{
    protected function _hasFieldReference(array $fields)
    {
        $regex = '/(OLD|NEW)[`]?\.[`]?(' . implode('|', $fields) . ')[`]?/i';

        foreach ($this->_statements as $statement) {
            if (preg_match($regex, (string)$statement)) {
                return true;
            }
        }

        return false;
    }
}
