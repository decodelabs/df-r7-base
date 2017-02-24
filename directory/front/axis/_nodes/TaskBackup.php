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

class TaskBackup extends arch\node\Task {

    const SCHEDULE = '0 0 * * 1';

    protected $_unitAdapters = [];
    protected $_backupAdapters = [];
    protected $_path;

    protected $_manifest = [
        'timestamp' => null,
        'master' => null,
        'connections' => []
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
        core\fs\Dir::create($this->_path);

        $this->io->writeLine('Backing up units');
        $this->io->writeLine();

        foreach($units as $inspector) {
            $this->_backupUnit($inspector);
        }

        $this->io->writeLine();
        $this->io->writeLine('Writing manifest file');
        $content = '<?php'."\n".'return '.core\collection\Util::exportArray($this->_manifest).';';
        file_put_contents($this->_path.'/manifest.php', $content, LOCK_EX);

        $this->io->writeLine('Archiving backup');
        $phar = new \PharData(dirname($this->_path).'/'.$backupId.'.tar');
        $phar->buildFromDirectory($this->_path);

        core\fs\Dir::delete($this->_path);
    }

    public function handleException(\Throwable $e) {
        core\fs\Dir::delete($this->_path);
        parent::handleException($e);
    }


// Units
    protected function _backupUnit($inspector) {
        switch($inspector->getType()) {
            case 'table':
                $this->_backupTable($inspector);
                break;

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
        $translator = new axis\schema\translator\Rdbms($unit, $backupAdapter, $schema);
        $opalSchema = $translator->createFreshTargetSchema();
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



// Backup adapters
    protected function _getBackupAdapter($inspector) {
        $unit = $inspector->getUnit();

        if($unit instanceof opal\query\IAdapter) {
            return $this->_getBackupAdapterFromQuerySourceUnit($unit);
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

    protected function _loadBackupAdapter($hash, $dbName, $connection) {
        $this->io->writeLine('Creating backup adapter '.$dbName);
        $backupAdapter = opal\rdbms\adapter\Base::factory('sqlite://'.$this->_path.'/'.$dbName.'.sqlite');
        $this->_backupAdapters[$hash] = $backupAdapter;
        $this->_manifest['connections'][$dbName.'.sqlite'] = $connection;

        return $backupAdapter;
    }
}