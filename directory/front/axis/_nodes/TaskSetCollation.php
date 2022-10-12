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
use df\opal;

use DecodeLabs\Terminus as Cli;

class TaskSetCollation extends arch\node\Task
{
    public const PREFIXES = ['legacy', 'r5', 'r7'];
    public const BLACKLIST = ['legacy_duk_chrdata'];

    public function execute(): void
    {
        $unit = $this->data->user->client;
        $adapter = $unit->getUnitAdapter()->getQuerySourceAdapter()->getAdapter();

        if ($adapter->getServerType() != 'mysql') {
            Cli::error('Default connection is '.$adapter->getServerType().', not mysql');
            return;
        }


        $server = $adapter->getServer();
        $collation = $this->request->query->get('collation', 'utf8mb4_unicode_ci');

        if (false === strpos($collation, '_')) {
            $collation .= '_unicode_ci';
        }

        $charset = $this->request->query->get('charset', explode('_', $collation)[0]);

        if (!Cli::confirm('Are you sure you want to convert all databases to '.$charset.' / '.$collation.'?', true)) {
            return;
        }


        foreach ($server->getDatabaseList() as $dbName) {
            if (in_array($dbName, self::BLACKLIST)) {
                continue;
            }

            $parts = explode('_', $dbName);

            if (!in_array($parts[0], self::PREFIXES)) {
                continue;
            }

            Cli::notice('Switching db: '.$dbName);
            Cli::{'yellow'}($dbName.' : '.$charset.' / '.$collation.' ');
            $database = $server->getDatabase($dbName);
            $database->setCharacterSet($charset, $collation, true);
            Cli::success('done');

            foreach ($database->getTableList() as $tableName) {
                Cli::{'yellow'}($tableName.' : '.$charset.' / '.$collation.' ');
                $table = $database->getTable($tableName);
                $table->setCharacterSet($charset, $collation);
                Cli::success('done');
            }

            Cli::newLine();
        }
    }
}
