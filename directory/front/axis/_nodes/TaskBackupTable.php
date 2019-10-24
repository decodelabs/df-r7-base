<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\axis\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\axis;
use df\opal;

class TaskBackupTable extends arch\node\Task
{
    protected $_unit;
    protected $_adapter;

    public function execute()
    {
        $unitId = $this->request['unit'];

        if (!$this->_unit = axis\Model::loadUnitFromId($unitId)) {
            throw core\Error::{'axis/unit/ENotFound'}(
                'Unit '.$unitId.' not found'
            );
        }

        if ($this->_unit->getUnitType() != 'table') {
            throw core\Error::{'axis/unit/EDomain'}(
                'Unit '.$unitId.' is not a table'
            );
        }

        if (!$this->_unit instanceof axis\ISchemaBasedStorageUnit) {
            throw Glitch::{'df/axis/unit/EDomain'}(
                'Unit '.$unitId.' is not schema based'
            );
        }

        $this->io->writeLine('Backing up unit '.$this->_unit->getUnitId());
        $this->_adapter = $this->_unit->getUnitAdapter();

        $parts = explode('\\', get_class($this->_adapter));
        $adapterName = array_pop($parts);

        $func = '_backup'.$adapterName.'Table';

        if (!method_exists($this, $func)) {
            throw core\Error::{'axis/unit/EDomain'}(
                'Table unit '.$unitId.' is using an adapter that doesn\'t currently support rebuilding'
            );
        }

        $schema = $this->_unit->buildInitialSchema();
        $this->_unit->updateUnitSchema($schema);
        $this->_unit->validateUnitSchema($schema);

        $this->{$func}($schema);
    }

    protected function _backupRdbmsTable(axis\schema\ISchema $axisSchema)
    {
        $this->io->writeLine('Switching to rdbms mode');

        $connection = $this->_adapter->getConnection();
        $currentTable = $this->_adapter->getQuerySourceAdapter();
        $dbSchema = $currentTable->getSchema();

        $currentTableName = $dbSchema->getName();
        $dbSchema->setName($currentTableName.axis\IUnitOptions::BACKUP_SUFFIX.$this->format->customDate('now', 'Ymd_his'));

        try {
            $this->io->writeLine('Building copy table');
            $newTable = $connection->createTable($dbSchema);
        } catch (opal\rdbms\ETableConflict $e) {
            throw core\Error::{'axis/unit/ERuntime'}(
                'Table unit '.$this->_unit->getUnitId().' is currently rebuilding in another process'
            );
        }

        $this->io->writeLine('Copying data...');
        $insert = $newTable->batchInsert();
        $count = 0;

        foreach ($currentTable->select() as $row) {
            $insert->addRow($row);
            $count++;
        }

        $insert->execute();
        $this->io->writeLine('Copied '.$count.' rows');
    }
}
