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

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\Glitch;
use DecodeLabs\Terminus as Cli;

class TaskBackup extends arch\node\Task
{
    protected $_unitAdapters = [];
    protected $_backupAdapters = [];
    protected $_path;

    protected $_manifest = [
        'timestamp' => null,
        'master' => null,
        'connections' => []
    ];

    public function execute(): void
    {
        axis\schema\Cache::getInstance()->clear();
        Cli::{'yellow'}('Probing units: ');

        $probe = new axis\introspector\Probe();
        $units = $probe->probeUnits();

        Cli::success(' found '.count($units));

        $this->_manifest['timestamp'] = time();
        $backupId = 'axis-'.date('YmdHis');
        $this->_path = Genesis::$hub->getSharedDataPath().'/backup/'.$backupId;
        Atlas::createDir($this->_path);

        $progressBar = Cli::newProgressBar(0, count($units), 0);
        $count = 0;

        foreach ($units as $inspector) {
            $this->_backupUnit($inspector);
            $progressBar->advance(++$count);
        }

        $progressBar->complete();

        Cli::newLine();
        Cli::{'yellow'}('Writing manifest file: ');
        $content = '<?php'."\n".'return '.core\collection\Util::exportArray($this->_manifest).';';
        file_put_contents($this->_path.'/manifest.php', $content, LOCK_EX);
        Cli::success('done');

        Cli::{'yellow'}('Archiving backup: ');
        $phar = new \PharData(dirname($this->_path).'/'.$backupId.'.tar');
        $phar->buildFromDirectory($this->_path);
        Cli::success('done');

        Atlas::deleteDir($this->_path);
    }

    public function handleException(\Throwable $e)
    {
        Atlas::deleteDir($this->_path);
        parent::handleException($e);
    }


    // Units
    protected function _backupUnit($inspector)
    {
        switch ($inspector->getType()) {
            case 'table':
                $this->_backupTable($inspector);
                break;

            default:
                break;
        }
    }

    protected function _backupTable($inspector)
    {
        if (!$inspector->storageExists()) {
            return;
        }

        $unitAdapter = $inspector->getAdapter();
        $unit = $inspector->getUnit();
        $backupAdapter = $this->_getBackupAdapter($inspector);
        $schema = $inspector->getSchema();
        $translator = new axis\schema\translator\Rdbms($unit, $backupAdapter, $schema);
        $opalSchema = $translator->createFreshTargetSchema();
        $table = $backupAdapter->createTable($opalSchema);
        //Cli::{'yellow'}('Copying table '.$inspector->getId().' to '.basename($backupAdapter->getDsn()->getDatabase()).': ');

        $insert = $table->batchInsert();
        $count = 0;

        foreach ($unit->getDelegateQueryAdapter()->select() as $row) {
            $insert->addRow($row);
            $count++;
        }

        $insert->execute();
        //Cli::success($count.' rows');
    }



    // Backup adapters
    protected function _getBackupAdapter($inspector)
    {
        $unit = $inspector->getUnit();

        if ($unit instanceof opal\query\IAdapter) {
            return $this->_getBackupAdapterFromQuerySourceUnit($unit);
        } else {
            Glitch::incomplete($unit);
        }
    }

    protected function _getBackupAdapterFromQuerySourceUnit($unit)
    {
        $hash = $unit->getQuerySourceAdapterHash();

        if (isset($this->_backupAdapters[$hash])) {
            return $this->_backupAdapters[$hash];
        }

        $unitAdapter = $unit->getUnitAdapter();
        $connection = $unitAdapter->getConnection();
        $this->_unitAdapters[$hash] = $connection;
        $dbName = $unitAdapter->getStorageGroupName();

        return $this->_loadBackupAdapter($hash, $dbName, (string)$connection->getDsn());
    }

    protected function _loadBackupAdapter($hash, $dbName, $connection)
    {
        Cli::inlineNotice('Creating backup adapter ');
        Cli::{'.brightMagenta'}($dbName);

        $backupAdapter = opal\rdbms\adapter\Base::factory('sqlite://'.$this->_path.'/'.$dbName.'.sqlite');
        $this->_backupAdapters[$hash] = $backupAdapter;
        $this->_manifest['connections'][$dbName.'.sqlite'] = $connection;

        return $backupAdapter;
    }
}
