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

class TaskBackup extends arch\task\Action {
    
    protected $_unitAdapters = [];
    protected $_backupAdapters = [];
    protected $_path;

    public function execute() {
        $this->response->write('Probing units...');

        $probe = new axis\introspector\Probe();
        $units = $probe->probeUnits();

        $this->response->writeLine(' found '.count($units).' to backup');

        $this->_path = $this->application->getSharedStoragePath().'/backup/axis-'.date('YmdHis');
        core\io\Util::ensureDirExists($this->_path);

        $this->response->writeLine('Backing up units on global cluster');
        $this->response->writeLine();

        foreach($units as $inspector) {
            $this->_backupUnit($inspector);
        }

        $clusterUnit = $this->data->getClusterUnit();

        if($clusterUnit) {
            $this->response->writeLine();

            foreach($clusterUnit->select('@primary as primary') as $row) {
                $key = (string)$row['primary'];
                $this->response->writeLine('Backing up units on cluster: '.$key);
                $this->response->writeLine();

                foreach($units as $inspector) {
                    $this->_backupUnit($inspector->getClusterVariant($key));
                }
            }
        }


        foreach($this->_unitAdapters as $hash => $adapter) {
            $adapter->closeConnection();
            unset($this->_unitAdapters[$hash]);
        }

        foreach($this->_backupAdapters as $hash => $adapter) {
            $adapter->closeConnection();
            unset($this->_backupAdapters[$hash]);
        }
    }

    public function handleException(\Exception $e) {
        core\io\Util::deleteDir($this->_path);
        parent::handleException($e);
    }


// Units
    protected function _backupUnit($inspector) {
        switch($inspector->getType()) {
            case 'table':
                $this->_backupTable($inspector);
                break;

            case 'schemaDefinition':
                $this->_backupSchemaDefinition($inspector);

            default:
                continue;
        }
    }

    protected function _backupTable($inspector) {
        if(!$inspector->storageExists()) {
            return;
        }

        $unitAdapter = $inspector->getAdapter();
        $unit = $inspector->getUnit();
        $backupAdapter = $this->_getBackupAdapter($inspector);
        $schema = $inspector->getTransientSchema();
        $bridge = new axis\schema\bridge\Rdbms($unit, $backupAdapter, $schema);
        $opalSchema = $bridge->createFreshTargetSchema();
        $table = $backupAdapter->createTable($opalSchema);
        $this->response->write('Copying table '.$inspector->getId().' to '.basename($backupAdapter->getDsn()->getDatabase()).' -');

        $insert = $table->batchInsert();
        $count = 0;

        foreach($unit->getDelegateQueryAdapter()->select() as $row) {
            $insert->addRow($row);
            $count++;
        }

        $insert->execute();
        $this->response->writeLine(' '.$count.' rows');
    }

    protected function _backupSchemaDefinition($inspector) {
        if(!$inspector->storageExists() || $inspector->getUnit()->getClusterId()) {
            return;
        }

        $unitAdapter = $inspector->getAdapter();
        $unit = $inspector->getUnit();
        $backupAdapter = $this->_getBackupAdapter($inspector);
        $schema = $inspector->getTransientSchema();
        $bridge = new axis\schema\bridge\Rdbms($unit, $backupAdapter, $schema);
        $opalSchema = $bridge->createFreshTargetSchema();
        $table = $backupAdapter->createTable($opalSchema);
        $this->response->write('Copying schema definition table to '.basename($backupAdapter->getDsn()->getDatabase()).' -');

        $insert = $table->batchInsert();
        $count = 0;

        foreach($unit->getUnitAdapter()->fetchRawData() as $row) {
            $insert->addRow($row);
            $count++;
        }

        $insert->execute();
        $this->response->writeLine(' '.$count.' rows');
    }



// Backup adapters
    protected function _getBackupAdapter($inspector) {
        $unit = $inspector->getUnit();

        if($unit instanceof opal\query\IAdapter) {
            return $this->_getBackupAdapterFromQuerySourceUnit($unit);
        } else if($unit instanceof axis\ISchemaDefinitionStorageUnit) {
            return $this->_getBackupAdapterForSchemaDefinitionUnit($unit);
        } else {
            core\stub($unit);
        }
    }

    protected function _getBackupAdapterFromQuerySourceUnit($unit) {
        $hash = $unit->getQuerySourceAdapterHash();

        if(isset($this->_backupAdapters[$hash])) {
            return $this->_backupAdapters[$hash];
        }

        $unitAdapter = $unit->getUnitAdapter();
        $this->_unitAdapters[$hash] = $unitAdapter->getConnection();
        $dbName = $unitAdapter->getStorageGroupName();

        return $this->_loadBackupAdapter($hash, $dbName);
    }

    protected function _getBackupAdapterForSchemaDefinitionUnit($unit) {
        $hash = $unit->getUnitAdapter()->getConnectionHash();

        if(isset($this->_backupAdapters[$hash])) {
            return $this->_backupAdapters[$hash];
        }

        $unitAdapter = $unit->getUnitAdapter();
        $this->_unitAdapters[$hash] = $unitAdapter->getConnection();
        $dbName = $unitAdapter->getStorageGroupName();
        return $this->_loadBackupAdapter($hash, $dbName);
    }

    protected function _loadBackupAdapter($hash, $dbName) {
        $this->response->writeLine('Creating backup adapter '.$dbName);
        $backupAdapter = opal\rdbms\adapter\Base::factory('sqlite://'.$this->_path.'/'.$dbName.'.sqlite');
        $this->_backupAdapters[$hash] = $backupAdapter;

        return $backupAdapter;
    }
}