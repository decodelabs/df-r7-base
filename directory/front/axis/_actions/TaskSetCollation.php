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
use df\opal;

class TaskSetCollation extends arch\task\Action {
    
    private static $_prefixes = [
        'legacy', 'r5', 'r7'
    ];

    private static $_blacklist = [
        'legacy_duk_chrdata'
    ];

    public function extractCliArguments(array $args) {
        $collation = $charset = null;

        if(isset($args[1])) {
            $collation = (string)$args[1];
            $charset = (string)$args[0];
        } else if(isset($args[0])) {
            $collation = (string)$args[0];
        }

        if($collation) {
            $this->request->query->collation = $collation;
        }

        if($charset) {
            $this->request->query->charset = $charset;
        }
    }

    public function execute() {
        $unit = $this->data->user->client;
        $adapter = $unit->getUnitAdapter()->getQuerySourceAdapter()->getAdapter();

        if($adapter->getServerType() != 'mysql') {
            $this->io->writeErrorLine('Default connection is '.$adapter->getServerType().', not mysql');
            return;
        }

        $server = $adapter->getServer();
        $collation = $this->request->query->get('collation', 'utf8_general_ci');

        if(false === strpos($collation, '_')) {
            $collation .= '_general_ci';
        }

        $charset = $this->request->query->get('charset', explode('_', $collation)[0]);

        foreach($server->getDatabaseList() as $dbName) {
            if(in_array($dbName, self::$_blacklist)) {
                continue;
            }

            $parts = explode('_', $dbName);

            if(!in_array($parts[0], self::$_prefixes)) {
                continue;
            }

            $this->io->writeLine('Updating DB '.$dbName.' to '.$charset.' / '.$collation);
            $database = $server->getDatabase($dbName);
            $database->setCharacterSet($charset, $collation, true);

            foreach($database->getTableList() as $tableName) {
                $this->io->writeLine('Converting table '.$tableName.' to '.$charset.' / '.$collation);
                $table = $database->getTable($tableName);
                $table->setCharacterSet($charset, $collation);
            }
            
            $this->io->writeLine();
        }

        $this->io->writeLine('Done');
    }
}