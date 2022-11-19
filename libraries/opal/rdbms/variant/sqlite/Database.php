<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\sqlite;

use DecodeLabs\Glitch;

use df\opal;

class Database extends opal\rdbms\Database
{
    public function getTableList()
    {
        $stmt = $this->_adapter->prepare('SELECT name FROM sqlite_master WHERE type=:a;');
        $stmt->bind(':a', 'table');
        $res = $stmt->executeRead();
        $output = [];

        foreach ($res as $row) {
            $output[] = $row['name'];
        }

        return $output;
    }

    public function rename($newName, $overwrite = false)
    {
        Glitch::incomplete($newName);
    }

    public function setCharacterSet($set, $collation = null)
    {
        Glitch::incomplete($set);
    }

    public function getCharacterSet()
    {
        return 'utf8';
    }

    public function setCollation($collation)
    {
        Glitch::incomplete($collation);
    }

    public function getCollation()
    {
        return 'BINARY';
    }
}
