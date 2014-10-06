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
            $this->_isEnabled = core\Environment::getInstance()->canUseDaemons();
        }

        return $this->_isEnabled;
    }

    public function ensureActivity() {
        if(!$this->isEnabled()) {
            return $this;
        }

        $path = df\Launchpad::$application->getLocalStoragePath().'/daemons/__activity';
        $launch = false;

        try {
            $mtime = filemtime($path);

            if(df\Launchpad::$startTime - $mtime > 300) {
                $launch = true;
            }
        } catch(\Exception $e) {
            $launch = true;
        }

        if($launch) {
            core\io\Util::ensureDirExists(dirname($path));
            touch($path);
            arch\task\Manager::getInstance()->launchBackground('daemons/ensure-activity');
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