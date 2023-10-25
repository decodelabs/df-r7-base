<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\axis\_nodes;

use DecodeLabs\Dictum;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Config\DataConnections as AxisConfig;
use DecodeLabs\Terminus as Cli;
use df\arch;
use df\opal;

class TaskSetMaster extends arch\node\Task
{
    public function execute(): void
    {
        $config = AxisConfig::load();
        $repository = $config->getConfigRepository();

        if ($repository->connections->master['adapter'] !== 'Rdbms') {
            Cli::error('Master is not using Rdbms adapter');
            return;
        }

        $current = $repository->connections->master['dsn'];

        if ($current === $config::DEFAULT_DSN) {
            $current = null;
        }

        $check = Dictum::toBoolean($this->request['check']);

        if (
            $current &&
            (
                !$check ||
                Cli::confirm('Use current: ' . opal\rdbms\Dsn::factory($current)->getDisplayString(true), true)
            )
        ) {
            if (!$check) {
                Cli::info('Sticking with current: ' . opal\rdbms\Dsn::factory($current)->getDisplayString(true));
            }

            return;
        }

        $dsn = new opal\rdbms\Dsn(
            'mysql://localhost/' . basename(dirname(
                Genesis::$hub->getApplicationPath()
            ))
        );

        do {
            $adapter = $this->_askFor('Adapter', function ($answer) {
                return $this->data->newValidator()
                    ->addRequiredField('adapter', 'enum')
                        ->setSanitizer(function ($value) {
                            return lcfirst($value);
                        })
                        ->setOptions(['mysql', 'mysqli', 'sqlite']);
            }, $dsn->getAdapter());
            $dsn->setAdapter($adapter);

            $username = $this->_askFor('Username', function ($answer) {
                return $this->data->newValidator()
                    ->addField('username', 'text');
            }, $dsn->getUsername());
            $dsn->setUsername($username);

            $password = Cli::askPassword('Password', false, false);
            $dsn->setPassword($password);

            $host = $this->_askFor('Host', function ($answer) {
                return $this->data->newValidator()
                    ->addRequiredField('host', 'text');
            }, $dsn->getHostname());
            $dsn->setHostname($host);

            $database = $this->_askFor('Database', function ($answer) {
                return $this->data->newValidator()
                    ->addRequiredField('database', 'text');
            }, $dsn->getDatabase());
            $dsn->setDatabase($database);

            if (!Cli::confirm('Is this correct? ' . $dsn->getDisplayString(true), true)) {
                continue;
            }

            try {
                $adapter = opal\rdbms\adapter\Base::factory($dsn, true);
            } catch (\Throwable $e) {
                Cli::error('!! Unable to connect');
                continue;
            }

            break;
        } while (true);

        $repository->connections->master->set('dsn', (string)$dsn);
        //$config->save();
    }
}
