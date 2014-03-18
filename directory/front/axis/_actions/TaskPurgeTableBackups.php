<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\axis\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\axis;
use df\opal;

class TaskPurgeTableBackups extends arch\task\Action {
    
    protected $_unit;
    protected $_adapter;

    protected function _run() {
        $unitId = $this->request->query['unit'];

        if(!$this->_unit = axis\Model::loadUnitFromId($unitId)) {
            $this->throwError(404, 'Unit '.$unitId.' not found');
        }

        if($this->_unit->getUnitType() != 'table') {
            $this->throwError(403, 'Unit '.$unitId.' is not a table');
        }

        if(!$this->_unit instanceof axis\IAdapterBasedStorageUnit) {
            $this->throwError(403, 'Table unit '.$unitId.' is not adapter based - don\'t know how to rebuild it!');
        }

        $this->response->writeLine('Purging backups for unit '.$this->_unit->getUnitId());
        $this->_adapter = $this->_unit->getUnitAdapter();

        $parts = explode('\\', get_class($this->_adapter));
        $adapterName = array_pop($parts);

        $func = '_purge'.$adapterName.'Table';

        if(!method_exists($this, $func)) {
            $this->throwError(403, 'Table unit '.$unitId.' is using an adapter that doesn\'t currently support rebuilding');
        }

        $inspector = new axis\introspector\UnitInspector($this->_unit);

        $this->{$func}($inspector->getBackups());
    }

    protected function _purgeRdbmsTable(array $backups) {
        $this->response->writeLine('Switching to rdbms mode');
        $connection = $this->_adapter->getConnection();

        foreach($backups as $backup) {
            $table = $connection->getTable($backup->name);
            $this->response->writeLine('Dropping table '.$backup->name);
            $table->drop();
        }
    }
}