<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\halo\daemon;

use DecodeLabs\Atlas;
use DecodeLabs\Exceptional;

use DecodeLabs\Genesis;
use df\arch;
use df\core;

class Manager implements IManager
{
    use core\TManager;

    public const REGISTRY_PREFIX = 'manager://daemon';

    protected $_isEnabled = null;

    public function isEnabled()
    {
        if ($this->_isEnabled === null) {
            $this->_isEnabled = core\environment\Config::getInstance()->canUseDaemons();
        }

        return $this->_isEnabled;
    }

    public function ensureActivity()
    {
        if (Genesis::$environment->isDevelopment()) {
            return $this;
        }

        $spoolOnly = false;

        if (!$this->isEnabled()) {
            $spoolOnly = true;
            //return $this;
        }

        $path = Genesis::$hub->getLocalDataPath() . '/daemons/__activity';
        $launch = false;

        try {
            $mtime = filemtime($path);

            if (Genesis::getStartTime() - $mtime > 300) {
                $launch = true;
            }
        } catch (\Throwable $e) {
            $launch = true;
        }

        if ($launch) {
            Atlas::createDir(dirname($path));
            touch($path);
            $taskManager = arch\node\task\Manager::getInstance();

            if ($spoolOnly) {
                $taskManager->launchBackground(
                    'tasks/spool',
                    null,
                    false,
                    false
                );
            } else {
                $taskManager->launchBackground(
                    'daemons/ensure-activity',
                    null,
                    false,
                    false
                );
            }
        }

        return $this;
    }

    public function launch($name)
    {
        if (!$this->isEnabled()) {
            throw Exceptional::Runtime(
                'Daemons are currently disabled in config'
            );
        }

        $remote = $this->getRemote($name);
        $remote->start();
        return $this;
    }

    public function nudge($name)
    {
        if (!$this->isEnabled()) {
            throw Exceptional::Runtime(
                'Daemons are currently disabled in config'
            );
        }

        $remote = $this->getRemote($name);
        $remote->nudge();
        return $this;
    }

    public function getRemote($name)
    {
        if (!$this->isEnabled()) {
            throw Exceptional::Runtime(
                'Daemons are currently disabled in config'
            );
        }

        return Remote::factory($name);
    }

    public function isRunning($name)
    {
        if (!$this->isEnabled()) {
            throw Exceptional::Runtime(
                'Daemons are currently disabled in config'
            );
        }

        return $this->getRemote($name)->isRunning();
    }
}
