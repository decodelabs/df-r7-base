<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\daemon;

use df;
use df\core;
use df\halo;
use df\arch;

class Manager implements IManager {

    use core\TManager;

    const REGISTRY_PREFIX = 'manager://daemon';

    protected $_isEnabled = null;

    public function isEnabled() {
        if($this->_isEnabled === null) {
            $this->_isEnabled = core\environment\Config::getInstance()->canUseDaemons();
        }

        return $this->_isEnabled;
    }

    public function ensureActivity() {
        $spoolOnly = false;

        if(!$this->isEnabled() || !df\Launchpad::$application->isProduction()) {
            $spoolOnly = true;
            //return $this;
        }

        $path = df\Launchpad::$application->getLocalStoragePath().'/daemons/__activity';
        $launch = false;

        try {
            $mtime = filemtime($path);

            if(df\Launchpad::$startTime - $mtime > 300) {
                $launch = true;
            }
        } catch(\Throwable $e) {
            $launch = true;
        }

        if($launch) {
            core\fs\Dir::create(dirname($path));
            touch($path);
            $taskManager = arch\node\task\Manager::getInstance();

            if($spoolOnly) {
                $taskManager->launchBackground('tasks/spool');
            } else {
                $taskManager->launchBackground('daemons/ensure-activity');
            }
        }

        return $this;
    }

    public function launch($name) {
        if(!$this->isEnabled()) {
            throw new RuntimeException(
                'Daemons are currently disabled in config'
            );
        }

        $remote = $this->getRemote($name);
        $remote->start();
        return $this;
    }

    public function nudge($name) {
        if(!$this->isEnabled()) {
            throw new RuntimeException(
                'Daemons are currently disabled in config'
            );
        }

        $remote = $this->getRemote($name);
        $remote->nudge();
        return $this;
    }

    public function getRemote($name) {
        if(!$this->isEnabled()) {
            throw new RuntimeException(
                'Daemons are currently disabled in config'
            );
        }

        return Remote::factory($name);
    }

    public function isRunning($name) {
        if(!$this->isEnabled()) {
            throw new RuntimeException(
                'Daemons are currently disabled in config'
            );
        }

        return $this->getRemote($name)->isRunning();
    }
}