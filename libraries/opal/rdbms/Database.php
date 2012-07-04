<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms;

use df;
use df\core;
use df\opal;
    
abstract class Database implements IDatabase {

    protected $_adapter;

    public static function factory(opal\rdbms\IAdapter $adapter, $name=null) {
    	$type = $adapter->getServerType();
    	$class = 'df\\opal\\rdbms\\variant\\'.$type.'\\Database';

    	if(!class_exists($class)) {
    		throw new RuntimeException(
    			'There is no database handler available for '.$type
			);
    	}

    	if($name !== null) {
    		core\stub('Database factory cannot yet switch database names in an adapter');
    	}

    	return new $class($adapter);
    }

    protected function __construct(opal\rdbms\IAdapter $adapter) {
    	$this->_adapter = $adapter;
    }

    public function getName() {
    	return $this->_adapter->getDsn()->getDatabase();
    }

    public function getAdapter() {
    	return $this->_adapter;
    }

	public function getTable($name) {
		return Table::factory($this->_adapter, $name);
	}


	public function drop() {
    	$stmt = $this->_adapter->prepare('DROP DATABASE IF EXISTS '.$this->getName());
    	$stmt->executeRaw();

    	return $this;
    }

	public function truncate() {
		foreach($this->getTableList() as $tableName) {
    		$table = $this->getTable($tableName);
    		$table->drop();
    	}

    	return $this;
	}
}