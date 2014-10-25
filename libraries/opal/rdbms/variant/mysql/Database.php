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
        $output = [];

        foreach($res as $row) {
            $output[] = $row[$key];
        }
        
        return $output;
    }

    public function rename($newName, $overwrite=false) {
        $tableList = $this->getTableList();
        $oldName = $this->getName();

        if($overwrite) {
            $this->_adapter->executeSql('DROP DATABASE `'.$newName.'`');
        }

        $encoding = $this->_adapter->getEncoding();
        $this->_adapter->executeSql('CREATE DATABASE `'.$newName.'` CHARACTER SET '.$encoding.' COLLATE '.$encoding.'_general_ci');

        foreach($tableList as $tableName) {
            $stmt = $this->_adapter->prepare('RENAME TABLE `'.$oldName.'`.`'.$tableName.'` TO `'.$newName.'`.`'.$tableName.'`');
            $res = $stmt->executeWrite();
        }

        $this->drop();
        $this->_adapter->switchDatabase($newName);
        return $this;
    }
}