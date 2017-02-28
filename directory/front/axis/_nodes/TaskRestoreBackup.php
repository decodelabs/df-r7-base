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

class TaskRestoreBackup extends arch\node\Task {

    protected $_path;
    protected $_sqlite = [];
    protected $_schemas = [];
    protected $_types = [];

    protected $_rdbmsAdapters = [];

    public function execute() {
        $fileName = basename($this->request['backup']);

        if(!preg_match('/^axis\-[0-9]+\.tar$/i', $fileName)) {
            $this->io->writeErrorLine('Not an axis backup file');
            return;
        }

        $file = $this->application->getSharedStoragePath().'/backup/'.$fileName;

        if(!is_file($file)) {
            $this->io->writeErrorLine('Backup not found');
        }

        if(!isset($this->request['noBackup'])) {
            $this->io->writeLine('Creating full backup...');
            $this->io->writeLine();
            $this->runChild('axis/backup');
            $this->io->writeLine();
        }

        $this->io->writeLine('Extracting backup '.$fileName);
        $this->_path = dirname($file).'/'.substr($fileName, 0, -4);

        core\fs\Dir::delete($this->_path);

        $phar = new \PharData($file);
        $phar->extractTo($this->_path);

        $manifestPath = $this->_path.'/manifest.php';
        $manifest = require $manifestPath;

        $this->io->write('Loading schemas...');

        $masterDb = $this->_loadSqlite($manifest['master']);
        $schemaTable = $masterDb->getTable('axis_schema');

        foreach($schemaTable->select() as $row) {
            // TODO: load temp virtual unit as alternative
            $unit = axis\Model::loadUnitFromId($row['unitId']);
            $row['schema'] = axis\schema\Base::fromJson($unit, $row['schema']);
            $row['unit'] = $unit;
            $this->_schemas[$row['storeName']] = $row;
        }

        $this->io->writeLine(' found '.count($this->_schemas));
        $this->io->writeLine();

        foreach($manifest['connections'] as $dbFileName => $connection) {
            $parts = explode('.', $dbFileName);
            $extension = array_pop($parts);
            $this->_types[$extension] = true;

            switch($extension) {
                case 'sqlite':
                    $this->_restoreSqlite($dbFileName, $connection);
                    break;

                default:
                    throw core\Error::{'axis/unit/EValue'}(
                        'Not really sure what to do with '.$extension.' type backups'
                    );
            }
        }


        foreach($this->_types as $type => $enabled) {
            $func = '_finalize'.ucfirst($type);

            if(!method_exists($this, $func)) {
                throw core\Error::{'axis/unit/ERuntime'}(
                    'Can\'t finalize '.$type.' adapters'
                );
            }

            $this->{$func}();
        }

        $this->io->writeLine('Clearing schema cache');
        axis\schema\Cache::getInstance()->clear();

        $this->io->writeLine('Cleaning up...');
        core\fs\Dir::delete($this->_path);
        core\fs\File::delete($file);

        $this->io->writeLine('Done');
    }


// Sqlite
    protected function _restoreSqlite($dbFileName, $dsn) {
        $backupDb = $this->_loadSqlite($dbFileName);
        $tableList = $backupDb->getDatabase()->getTableList();
        $dsn = opal\rdbms\Dsn::factory($dsn);
        $dsnString = (string)$dsn;

        $this->io->writeLine('Restoring '.$dsn->getDisplayString());

        $dsn->setDatabaseSuffix('__restore');
        $adapter = opal\rdbms\adapter\Base::factory($dsn, true);
        $adapter->getDatabase()->truncate();
        $this->_rdbmsAdapters[$dsnString] = $adapter;

        foreach($tableList as $tableName) {
            if(!isset($this->_schemas[$tableName])) {
                throw core\Error::{'axis/schema/ENotFound'}(
                    'Schema not found for '.$tableName
                );
            }

            $schemaSet = $this->_schemas[$tableName];
            $schema = $schemaSet['schema'];

            $this->io->write('Building table '.$schemaSet['storeName'].'...');

            $translator = new axis\schema\translator\Rdbms($schemaSet['unit'], $adapter, $schema);
            $dbSchema = $translator->createFreshTargetSchema();
            $newTable = $adapter->getTable($schemaSet['storeName'])->create($dbSchema);
            $oldTable = $backupDb->getTable($tableName);
            $insert = $newTable->batchInsert();

            foreach($oldTable->select() as $row) {
                $insert->addRow($row);
            }

            $count = $insert->execute();
            $this->io->writeLine(' '.$count.' rows');
        }

        $this->io->writeLine();
    }

    protected function _finalizeSqlite() {
        foreach($this->_rdbmsAdapters as $adapter) {
            $dsn = clone $adapter->getDsn();
            $dsn->setDatabaseSuffix(null);
            $this->io->writeLine('Replacing db '.$dsn->getDisplayString());

            $keyName = $dsn->getDatabaseKeyName();
            $adapter->getDatabase()->rename($keyName, true);
        }
    }

    protected function _loadSqlite($dbFileName) {
        if(!isset($this->_sqlite[$dbFileName])) {
            $this->_sqlite[$dbFileName] = opal\rdbms\adapter\Base::factory('sqlite://'.$this->_path.'/'.$dbFileName);
        }

        return $this->_sqlite[$dbFileName];
    }


// Node
    public function handleException(\Throwable $e) {
        if($this->_path) {
            core\fs\Dir::delete($this->_path);
        }

        parent::handleException($e);
    }
}