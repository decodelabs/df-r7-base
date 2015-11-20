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

class TaskRebuildTable extends arch\node\Task {

    protected $_deleteOld = false;

    public function execute() {
        $this->task->shouldCaptureBackgroundTasks(true);
        $unitId = $this->request['unit'];

        if(!$unit = axis\Model::loadUnitFromId($unitId)) {
            $this->throwError(404, 'Unit '.$unitId.' not found');
        }

        if($unit->getUnitType() != 'table') {
            $this->throwError(403, 'Unit '.$unitId.' is not a table');
        }

        if(!$unit instanceof axis\IAdapterBasedStorageUnit) {
            $this->throwError(403, 'Table unit '.$unitId.' is not adapter based - don\'t know how to rebuild it!');
        }

        $this->_deleteOld = isset($this->request['delete']);

        $this->io->writeLine('Rebuilding unit '.$unit->getUnitId());
        $adapter = $unit->getUnitAdapter();

        $parts = explode('\\', get_class($adapter));
        $adapterName = array_pop($parts);

        $func = '_rebuild'.$adapterName.'Table';

        if(!method_exists($this, $func)) {
            $this->throwError(403, 'Table unit '.$unitId.' is using an adapter that doesn\'t currently support rebuilding');
        }

        $schema = $unit->buildInitialSchema();
        $unit->updateUnitSchema($schema);
        $unit->validateUnitSchema($schema);

        $this->{$func}($unit, $schema);

        $this->io->writeLine();
        $this->io->writeLine('Updating schema cache');

        axis\schema\Cache::getInstance()->clearAll();
        axis\schema\Manager::getInstance()->store($unit, $schema);

        gc_collect_cycles();
        $this->io->writeLine('Done');
    }

    protected function _rebuildRdbmsTable(axis\IStorageUnit $unit, axis\schema\ISchema $axisSchema) {
        $this->io->writeLine('Switching to rdbms mode');

        $adapter = $unit->getUnitAdapter();
        $connection = $adapter->getConnection();
        $newConnection = clone $connection;
        $currentTable = clone $adapter->getQuerySourceAdapter();

        if(!$currentTable->exists()) {
            $this->io->writeLine('Unit rdbms table '.$currentTable->getName().' not found - nothing to do');
            return;
        }

        $translator = new axis\schema\translator\Rdbms($unit, $connection, $axisSchema);
        $dbSchema = $translator->createFreshTargetSchema();
        $currentTableName = $dbSchema->getName();
        $dbSchema->setName($currentTableName.'__rebuild__');

        try {
            $this->io->writeLine('Building copy table');
            $newTable = $newConnection->createTable($dbSchema);
        } catch(opal\rdbms\TableConflictException $e) {
            $this->throwError(403, 'Table unit '.$unit->getUnitId().' is currently rebuilding in another process');
        }

        $this->io->writeLine('Copying data...');
        $insert = $newTable->batchInsert();
        $count = 0;

        $fields = $dbSchema->getFields();
        $currentFields = $currentTable->getSchema()->getFields();
        $generatorFields = [];

        foreach($fields as $fieldName => $field) {
            if(isset($currentFields[$fieldName])) {
                continue;
            }

            $axisField = $axisSchema->getField($fieldName);

            if($axisField instanceof opal\schema\IAutoGeneratorField) {
                $generatorFields[$fieldName] = $axisField;
            }
        }



        foreach($currentTable->select()->isUnbuffered(true) as $row) {
            foreach($row as $key => $value) {
                if(!isset($fields[$key])) {
                    unset($row[$key]);
                }
            }

            foreach($generatorFields as $fieldName => $axisField) {
                $row[$fieldName] = $axisField->generateInsertValue($row);
            }

            $insert->addRow($row);
            $count++;
        }

        $insert->execute();
        $this->io->writeLine('Copied '.$count.' rows');

        $this->io->writeLine('Renaming tables');

        if($this->_deleteOld) {
            $currentTable->drop();
        } else {
            $currentTable->rename($currentTableName.axis\IUnitOptions::BACKUP_SUFFIX.$this->format->customDate('now', 'Ymd_his'));
        }

        $newTable->rename($currentTableName);
    }
}