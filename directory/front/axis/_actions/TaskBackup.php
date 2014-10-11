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
    
    const SCHEDULE = '0 0 * * 1';

    protected $_unitAdapters = [];
    protected $_backupAdapters = [];
    protected $_path;

    protected $_manifest = [
        'timestamp' => null,
        'master' => null,
        'connections' => [],
        'clusterUnit' => null
    ];

    public function execute() {
        axis\schema\Cache::getInstance()->clear();
        $this->io->write('Probing units...');

        $probe = new axis\introspector\Probe();
        $units = $probe->probeUnits();

        $this->io->writeLine(' found '.count($units).' to backup');

        $this->_manifest['timestamp'] = time();
        $backupId = 'axis-'.date('YmdHis');
        $this->_path = $this->application->getSharedStoragePath().'/backup/'.$backupId;
        core\io\Util::ensureDirExists($this->_path);

        $this->io->writeLine('Backing up units on global cluster');
        $this->io->writeLine();

        foreach($units as $inspector) {
            $this->_backupUnit($inspector);
        }

        $clusterUnit = $this->data->getClusterUnit();

        if($clusterUnit) {
            $this->_manifest['clusterUnit'] = $clusterUnit->getUnitId();

            $this->io->writeLine();

            foreach($clusterUnit->select('@primary as primary') as $row) {
                $key = (string)$row['primary'];
                $this->io->writeLine('Backing up units on cluster: '.$key);
                $this->io->writeLine();

                foreach($units as $inspector) {
                    $this->_backupUnit($inspector->getClusterVariant($key));
                }
            }
        }

        $this->io->writeLine();
        $this->io->writeLine('Writing manifest file');
        $content = '<?php'."\n".'return '.core\collection\Util::exportArray($this->_manifest).';';
        file_put_contents($this->_path.'/manifest.php', $content, LOCK_EX);

        $this->io->writeLine('Archiving backup');
        $phar = new \PharData(dirname($this->_path).'/'.$backupId.'.tar');
        $phar->buildFromDirectory($this->_path);

        core\io\Util::deleteDir($this->_path);
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
        $schema = $inspector->getSchema();
        $bridge = new axis\schema\bridge\Rdbms($unit, $backupAdapter, $schema);
        $opalSchema = $bridge->createFreshTargetSchema();
        $table = $backupAdapter->createTable($opalSchema);
        $this->io->write('Copying table '.$inspector->getId().' to '.basename($backupAdapter->getDsn()->getDatabase()).' -');

        $insert = $table->batchInsert();
        $count = 0;

        foreach($unit->getDelegateQueryAdapter()->select() as $row) {
            $insert->addRow($row);
            $count++;
        }

        $insert->execute();
        $this->io->writeLine(' '.$count.' rows');
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
        $this->io->write('Copying schema definition table to '.basename($backupAdapter->getDsn()->getDatabase()).' -');

        $insert = $table->batchInsert();
        $count = 0;

        foreach($unit->getUnitAdapter()->fetchRawData() as $row) {
            $insert->addRow($row);
            $count++;
        }

        $insert->execute();
        $this->io->writeLine(' '.$count.' rows');
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
        $connection = $unitAdapter->getConnection();
        $this->_unitAdapters[$hash] = $connection;
        $dbName = $unitAdapter->getStorageGroupName();

        return $this->_loadBackupAdapter($hash, $dbName, (string)$connection->getDsn());
    }

    protected function _getBackupAdapterForSchemaDefinitionUnit($unit) {
        $hash = $unit->getUnitAdapter()->getConnectionHash();

        if(!isset($this->_backupAdapters[$hash])) {
            $unitAdapter = $unit->getUnitAdapter();
            $connection = $unitAdapter->getConnection();
            $this->_unitAdapters[$hash] = $connection;
            $dbName = $unitAdapter->getStorageGroupName();
            $this->_loadBackupAdapter($hash, $dbName, (string)$connection->getDsn());
        }

        $this->_manifest['master'] = basename($this->_backupAdapters[$hash]->getDsn()->getDatabase());

        return $this->_backupAdapters[$hash];
        
    }

    protected function _loadBackupAdapter($hash, $dbName, $connection) {
        $this->io->writeLine('Creating backup adapter '.$dbName);
        $backupAdapter = opal\rdbms\adapter\Base::factory('sqlite://'.$this->_path.'/'.$dbName.'.sqlite');
        $this->_backupAdapters[$hash] = $backupAdapter;
        $this->_manifest['connections'][$dbName.'.sqlite'] = $connection;

        return $backupAdapter;
    }
}