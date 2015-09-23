<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\migrate\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\axis;

class TaskUpgradeBuckets extends arch\task\Action {
    
    protected $_connection;

    public function execute() {
        $this->_connection = $this->data->media->file->getUnitAdapter()->getConnection();

        $newBucketTable = $this->_buildNewTable('bucket');

        $this->_mapBuckets($newBucketTable);

        $this->_swapTables();
        $this->_clearSchemaCache();
    }

    protected function _buildNewTable($unitName) {
        $this->io->writeLine('Building new '.$unitName.' table');
        $unit = $this->data->media->{$unitName};

        return $this->_generateTable($unit);
    }

    protected function _generateTable($unit, $targetName=null) {
        $schema = $unit->buildInitialSchema();
        $unit->updateUnitSchema($schema);
        $unit->validateUnitSchema($schema);

        $translator = new axis\schema\translator\Rdbms($unit, $this->_connection, $schema);
        $dbSchema = $translator->createFreshTargetSchema();

        if($targetName === null) {
            $targetName = $dbSchema->getName();
        }

        $dbSchema->setName($targetName.'__new__');

        $newConnection = clone $this->_connection;
        return $newConnection->createTable($dbSchema, true);
    }


    protected function _mapBuckets($bucketTable) {
        $this->io->writeLine('Mapping buckets');
        $oldTable = $this->_connection->getTable('media_bucket');

        foreach($oldTable->select() as $row) {
            $bucketTable->insert($row)->execute();
        }
    }

    protected function _swapTables() {
        $this->io->writeLine('Swapping tables');

        $swapTables = [
            'media_bucket'
        ];

        foreach($swapTables as $name) {
            $this->_connection->getTable($name)->drop();
            $table = $this->_connection->getTable($name.'__new__');

            if(!$table->exists()) {
                continue;
            }

            $table->rename($name);
        }
    }

    protected function _clearSchemaCache() {
        $this->io->writeLine('Updating schema cache');
        
        $clearTables = [
            'media_bucket'
        ];

        $this->_connection->getTable('axis_schema')->delete()
            ->where('storeName', 'in', $clearTables)
            ->execute();

        axis\schema\Cache::getInstance()->clearAll();
    }
}