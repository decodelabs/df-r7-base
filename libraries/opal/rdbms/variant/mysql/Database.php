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

    public function setCharacterSet($set, $collation=null) {
        $sql = 'ALTER DATABASE `'.$this->getName().'` CHARACTER SET :set';  

        if($collation !== null) {
            $sql .= ' COLLATE :collation';
        }

        $stmt = $this->_adapter->prepare($sql);
        $stmt->bind('set', $set);

        if($collation !== null) {
            $stmt->bind('collation', $collation);
        }

        $stmt->executeWrite();
        return $this;
    }

    public function getCharacterSet() {
        $stmt = $this->_adapter->prepare('SELECT default_character_set_name FROM information_schema.SCHEMATA S WHERE schema_name = :name');
        $stmt->bind('name', $this->getName());
        $res = $stmt->executeRead();

        foreach($res as $row) {
            return $row['default_character_set_name'];
        }

        return 'utf8';
    }

    public function setCollation($collation) {
        $stmt = $this->_adapter->prepare('ALTER DATABASE `'.$this->getName().'` COLLATE :collation');
        $stmt->bind('collation', $collation);
        $stmt->executeWrite();
        return $this;
    }

    public function getCollation() {
        $stmt = $this->_adapter->prepare('SELECT default_collation_name FROM information_schema.SCHEMATA S WHERE schema_name = :name');
        $stmt->bind('name', $this->getName());
        $res = $stmt->executeRead();

        foreach($res as $row) {
            return $row['default_collation_name'];
        }

        return 'utf8_general_ci';
    }
}