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

class TaskRebuildTable extends arch\task\Action {
    
    public function execute() {
        $unitId = $this->request->query['unit'];

        if(!$unit = axis\Model::loadUnitFromId($unitId)) {
            $this->throwError(404, 'Unit '.$unitId.' not found');
        }

        if($unit->getUnitType() != 'table') {
            $this->throwError(403, 'Unit '.$unitId.' is not a table');
        }

        if(!$unit instanceof axis\IAdapterBasedStorageUnit) {
            $this->throwError(403, 'Table unit '.$unitId.' is not adapter based - don\'t know how to rebuild it!');
        }

        $this->io->writeLine('Rebuilding unit '.$unit->getUnitId().' in global cluster');
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

        if($clusterUnit = $this->data->getClusterUnit()) {
            foreach($clusterUnit->select('@primary')->toList('@primary') as $clusterId) {
                $this->io->writeLine();
                $this->io->writeLine('Rebuilding in cluster: '.$clusterId);

                $unit = axis\Model::loadUnitFromId($unitId, $clusterId);
                $this->{$func}($unit, $schema);
            }
        }

        $this->io->writeLine();
        $this->io->writeLine('Updating schema cache');
        
        axis\schema\Cache::getInstance()->clear();

        $schemaDefinition = new axis\unit\schemaDefinition\Virtual($unit->getModel());
        $schemaDefinition->store($unit, $schema);

        $this->io->writeLine('Done');
    }

    protected function _rebuildRdbmsTable(axis\IStorageUnit $unit, axis\schema\ISchema $axisSchema) {
        $this->io->writeLine('Switching to rdbms mode');

        $adapter = $unit->getUnitAdapter();
        $connection = $adapter->getConnection();
        $currentTable = $adapter->getQuerySourceAdapter();

        if(!$currentTable->exists()) {
            $this->io->writeLine('Unit rdbms table '.$currentTable->getName().' not found - nothing to do');
            return;
        }

        $bridge = new axis\schema\bridge\Rdbms($unit, $connection, $axisSchema);
        $dbSchema = $bridge->createFreshTargetSchema();
        $currentTableName = $dbSchema->getName();
        $dbSchema->setName($currentTableName.'__rebuild__');

        try {
            $this->io->writeLine('Building copy table');
            $newTable = $connection->createTable($dbSchema);
        } catch(opal\rdbms\TableConflictException $e) {
            $this->throwError(403, 'Table unit '.$unit->getUnitId().' is currently rebuilding in another process');
        }

        $this->io->writeLine('Copying data...');
        $insert = $newTable->batchInsert();
        $count = 0;

        $fields = $dbSchema->getFields();

        foreach($currentTable->select() as $row) {
            foreach($row as $key => $value) {
                if(!isset($fields[$key])) {
                    unset($row[$key]);
                }
            }

            $insert->addRow($row);
            $count++;
        }

        $insert->execute();
        $this->io->writeLine('Copied '.$count.' rows');

        $this->io->writeLine('Renaming tables');
        $currentTable->rename($currentTableName.axis\IUnit::BACKUP_SUFFIX.$this->format->customDate('now', 'Ymd_his'));
        $newTable->rename($currentTableName);
    }
}