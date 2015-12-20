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

class TaskPurgeTableBackups extends arch\node\Task {

    public function execute() {
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

        $this->io->writeLine('Purging backups for unit '.$unit->getUnitId());

        $adapter = $unit->getUnitAdapter();

        $parts = explode('\\', get_class($adapter));
        $adapterName = array_pop($parts);

        $func = '_purge'.$adapterName.'Table';

        if(!method_exists($this, $func)) {
            $this->throwError(403, 'Table unit '.$unitId.' is using an adapter that doesn\'t currently support rebuilding');
        }

        $inspector = new axis\introspector\UnitInspector($unit);
        $this->{$func}($unit, $inspector->getBackups());
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