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

class TaskSetMaster extends arch\node\Task {

    public function execute() {
        $config = axis\Config::getInstance();

        if($config->values->connections->master['adapter'] !== 'Rdbms') {
            $this->io->writeErrorLine('Master is not using Rdbms adapter');
            return;
        }

        $current = $config->values->connections->master['dsn'];

        if($current === $config::DEFAULT_DSN) {
            $current = null;
        }

        $check = $this->format->stringToBoolean($this->request['check'], true);

        if($current && (!$check || $this->_askBoolean('Use current: '.opal\rdbms\Dsn::factory($current)->getDisplayString(true), true))) {
            if(!$check) {
                $this->io->writeLine('Sticking with current: '.opal\rdbms\Dsn::factory($current)->getDisplayString(true));
            }

            return;
        }

        $dsn = new opal\rdbms\Dsn('mysql://localhost/'.basename(dirname($this->application->getApplicationPath())));

        do {
            $adapter = $this->_askFor('Adapter', function($answer) {
                return $this->data->newValidator()
                    ->addRequiredField('adapter', 'enum')
                        ->setSanitizer(function($value) {
                            return lcfirst($value);
                        })
                        ->setOptions(['mysql', 'mysqli', 'sqlite']);
            }, $dsn->getAdapter());
            $dsn->setAdapter($adapter);

            $username = $this->_askFor('Username', function($answer) {
                return $this->data->newValidator()
                    ->addField('username', 'text');
            }, $dsn->getUsername());
            $dsn->setUsername($username);

            $password = $this->_askPassword('Password', false, false);
            $dsn->setPassword($password);

            $host = $this->_askFor('Host', function($answer) {
                return $this->data->newValidator()
                    ->addRequiredField('host', 'text');
            }, $dsn->getHostname());
            $dsn->setHostname($host);

            $database = $this->_askFor('Database', function($answer) {
                return $this->data->newValidator()
                    ->addRequiredField('database', 'text');
            }, $dsn->getDatabase());
            $dsn->setDatabase($database);

            if(!$this->_askBoolean('Is this correct? '.$dsn->getDisplayString(true), true)) {
                continue;
            }

            try {
                $adapter = opal\rdbms\adapter\Base::factory($dsn, true);
            } catch(\Exception $e) {
                $this->io->writeErrorLine('!! Unable to connect');
                continue;
            }

            break;
        } while(true);

        $config->values->connections->master->dsn = (string)$dsn;
        $config->save();
    }
}