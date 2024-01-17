<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\axis\_nodes;

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\Systemic;
use DecodeLabs\Terminus as Cli;

use df\arch;

class TaskMariadbDump extends arch\node\Task
{
    public function execute(): void
    {
        $conn = $this->data->user->client->getUnitAdapter()->getConnection();
        $dsn = $conn->getDsn();

        $filename = $dsn->getDatabase() . '-' . date('YmdHis') . '.sql';
        $path = Genesis::$hub->getSharedDataPath() . '/backup/' . $filename;
        Atlas::createDir(dirname($path));

        Cli::{'..yellow'}('Dumping database');
        Cli::{'..white'}('mariadb-dump --single-transaction --quick ' . $dsn->getDatabase() . ' > ' . $filename);

        Systemic::command(implode(' ', [
                'mariadb-dump',
                '--user="' . $dsn->getUsername() . '"',
                '--password="' . $dsn->getPassword() . '"',
                '--single-transaction',
                '--quick',
                $dsn->getDatabase() . ' > "' . $path . '"'
            ]))
            ->addSignal('SIGINT', 'SIGTERM', 'SIGQUIT')
            ->run();

        Cli::success(' done');
    }
}
