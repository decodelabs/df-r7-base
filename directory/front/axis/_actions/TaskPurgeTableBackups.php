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

    public function execute() {
        $unitId = $this->request['unit'];
        $allClusters = isset($this->request['allClusters']);

        if(!$unit = axis\Model::loadUnitFromId($unitId)) {
            $this->throwError(404, 'Unit '.$unitId.' not found');
        }

        $isClusterUnit = (bool)$unit->getClusterId();

        if($isClusterUnit && $allClusters) {
            $unit = axis\Model::loadUnitFromId($unit->getGlobalUnitId());
        }

        if($unit->getUnitType() != 'table') {
            $this->throwError(403, 'Unit '.$unitId.' is not a table');
        }

        if(!$unit instanceof axis\IAdapterBasedStorageUnit) {
            $this->throwError(403, 'Table unit '.$unitId.' is not adapter based - don\'t know how to rebuild it!');
        }

        if($isClusterUnit) {
            $this->io->writeLine('Purging backups for unit '.$unit->getUnitId().' in cluster: '.$unit->getClusterId());
        } else {
            $this->io->writeLine('Purging backups for unit '.$unit->getUnitId().' in global cluster');
        }

        $adapter = $unit->getUnitAdapter();

        $parts = explode('\\', get_class($adapter));
        $adapterName = array_pop($parts);

        $func = '_purge'.$adapterName.'Table';

        if(!method_exists($this, $func)) {
            $this->throwError(403, 'Table unit '.$unitId.' is using an adapter that doesn\'t currently support rebuilding');
        }

        $inspector = new axis\introspector\UnitInspector($unit);
        $this->{$func}($unit, $inspector->getBackups());

        if($allClusters && ($clusterUnit = $this->data->getClusterUnit())) {
            foreach($clusterUnit->select('@primary')->toList('@primary') as $clusterId) {
                $this->io->writeLine();
                $this->io->writeLine('Purging in cluster: '.$clusterId);

                $unit = axis\Model::loadUnitFromId($unitId, $clusterId);
                $inspector = new axis\introspector\UnitInspector($unit);
                $this->{$func}($unit, $inspector->getBackups());
            }
        }
    }

    protected function _purgeRdbmsTable(axis\IStorageUnit $unit, array $backups) {
        $this->io->writeLine('Switching to rdbms mode');

        $adapter = $unit->getUnitAdapter();
        $connection = $adapter->getConnection();
        $count = 0;

        foreach($backups as $backup) {
            $table = $connection->getTable($backup->name);
            $this->io->writeLine('Dropping table '.$backup->name);
            $table->drop();
            $count++;
        }

        if(!$count) {
            $this->io->writeLine('No backup tables to drop');
        }
    }
}