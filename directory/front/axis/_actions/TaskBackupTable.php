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

class TaskBackupTable extends arch\task\Action {
    
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

        $this->response->writeLine('Backing up unit '.$this->_unit->getUnitId());
        $this->_adapter = $this->_unit->getUnitAdapter();

        $parts = explode('\\', get_class($this->_adapter));
        $adapterName = array_pop($parts);

        $func = '_backup'.$adapterName.'Table';

        if(!method_exists($this, $func)) {
            $this->throwError(403, 'Table unit '.$unitId.' is using an adapter that doesn\'t currently support rebuilding');
        }

        $schema = $this->_unit->buildInitialSchema();
        $this->_unit->updateUnitSchema($schema);
        $this->_unit->validateUnitSchema($schema);

        $this->{$func}($schema);
    }

    protected function _backupRdbmsTable(axis\schema\ISchema $axisSchema) {
        $this->response->writeLine('Switching to rdbms mode');

        $connection = $this->_adapter->getConnection();
        $currentTable = $this->_adapter->getQuerySourceAdapter();
        $dbSchema = $currentTable->getSchema();
        
        $currentTableName = $dbSchema->getName();
        $dbSchema->setName($currentTableName.axis\IUnit::BACKUP_SUFFIX.$this->format->customDate('now', 'Ymd_his'));

        try {
            $this->response->writeLine('Building copy table');
            $newTable = $connection->createTable($dbSchema);
        } catch(opal\rdbms\TableConflictException $e) {
            $this->throwError(403, 'Table unit '.$this->_unit->getUnitId().' is currently rebuilding in another process');
        }

        $this->response->writeLine('Copying data...');
        $insert = $newTable->batchInsert();
        $count = 0;

        foreach($currentTable->select() as $row) {
            $insert->addRow($row);
            $count++;
        }

        $insert->execute();
        $this->response->writeLine('Copied '.$count.' rows');
    }
}