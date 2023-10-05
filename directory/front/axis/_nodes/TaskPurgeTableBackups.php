<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\axis\_nodes;

use DecodeLabs\Exceptional;
use DecodeLabs\Terminus as Cli;

use df\arch;
use df\axis;

class TaskPurgeTableBackups extends arch\node\Task
{
    public function prepareArguments(): array
    {
        return Cli::$command
            ->addArgument('?unit', 'Unit to purge')
            ->toArray();
    }

    public function execute(): void
    {
        $unitId = $this->request['unit'];

        if (!$unit = axis\Model::loadUnitFromId($unitId)) {
            throw Exceptional::{'df/axis/unit/NotFound'}(
                'Unit ' . $unitId . ' not found'
            );
        }

        if ($unit->getUnitType() != 'table') {
            throw Exceptional::{'df/axis/unit/Domain'}(
                'Unit ' . $unitId . ' is not a table'
            );
        }

        if (!$unit instanceof axis\IAdapterBasedStorageUnit) {
            throw Exceptional::{'df/axis/unit/Domain'}(
                'Table unit ' . $unitId . ' is not adapter based - don\'t know how to rebuild it!'
            );
        }

        Cli::info('Purging backups for unit ' . $unit->getUnitId());

        $adapter = $unit->getUnitAdapter();

        $parts = explode('\\', get_class($adapter));
        $adapterName = array_pop($parts);

        $func = '_purge' . $adapterName . 'Table';

        if (!method_exists($this, $func)) {
            throw Exceptional::{'df/axis/unit/Domain'}(
                'Table unit ' . $unitId . ' is using an adapter that doesn\'t currently support rebuilding'
            );
        }

        $inspector = new axis\introspector\UnitInspector($unit);
        $this->{$func}($unit, $inspector->getBackups());
    }

    protected function _purgeRdbmsTable(axis\IAdapterBasedStorageUnit $unit, array $backups)
    {
        Cli::info('Switching to rdbms mode');

        $adapter = $unit->getUnitAdapter();
        $connection = $adapter->getConnection();
        $count = 0;

        foreach ($backups as $backup) {
            $table = $connection->getTable($backup->name);
            Cli::{'yellow'}($backup->name . ' ');
            $table->drop();
            $count++;
            Cli::success('dropped');
        }

        if (!$count) {
            Cli::notice('No backup tables to drop');
        }
    }
}
