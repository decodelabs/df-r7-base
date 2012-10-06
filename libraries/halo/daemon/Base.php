<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\daemon;

use df;
use df\core;
use df\halo;
    
abstract class Base implements IDaemon {

    use halo\event\TDispatcherProvider;

    const PAUSED_SLEEP_TIME = 4;
    const REQUIRES_PRIVILEGED_PROCESS = true;

    protected $_process;

    protected $_isStarted = false;
    protected $_isStopping = false;
    protected $_isStopped = false;
    protected $_isPaused = false;
    protected $_isForked = false;

    public static function factory($name) {
        $parts = explode('/', $name);
        $top = ucfirst(array_pop($parts));
        $parts[] = $top;
        $class = 'df\\apex\\daemons\\'.implode('\\', $parts);

        if(!class_exists($class)) {
            throw new RuntimeException(
                'Daemon '.$name.' could not be found'
            );
        }

        return new $class();
    }

    protected function __construct() {}

    public function getName() {
        $parts = array_slice(explode('\\', get_class($this)), 3);
        return implode('/', $parts);
    }


// Runtime
    final public function start() {
        if($this->_isStarted) {
            throw new LogicException(
                'Daemon '.$this->getName().' is already running'
            );  
        }

        gc_enable();

        $this->getDispatcher();
        $this->_process = halo\process\Base::getCurrent();
        $system = halo\system\Base::getInstance();
        $isPrivileged = $this->_process->isPrivileged();

        if(!$isPrivileged && static::REQUIRES_PRIVILEGED_PROCESS) {
            throw new RuntimeException(
                'Daemon '.$this->getName().' must be running from a privileged process'
            );
        }

        if($this->_process->canFork()) {
            if($this->_process->fork()) {
                exit;
            } else {
                $this->_isForked = true;
            }
        }

        $this->_process->setTitle(df\Launchpad::$application->getName().' - '.$this->getName());

        if($isPrivileged) {
            if($system->getPlatformType() == 'Unix') {
                $pidPath = $this->_getPidFilePath();

                if($pidPath) {
                    $this->_process->setPidFilePath($pidPath);
                }
            }

            $this->_preparePrivilegedResources();

            $user = $this->_getDaemonUser();
            $group = $this->_getDaemonGroup();

            $this->_process->setIdentity($user, $group);
        }

        declare(ticks = 1);
        $this->_isStarted = true;

        $this->_setup();

        $this->_dispatcher
            ->setCycleHandler([$this, 'cycle'])
            ->setSignalHandler(['SIGHUP'], function() {})
            ->setSignalHandler(['SIGTERM', 'SIGINT'], [$this, 'stop'])
            ->setSignalHandler(['SIGTSTP'], [$this, 'pause'])
            ->setSignalHandler(['SIGCONT'], [$this, 'resume']);


        while(true) {
            if($this->_isPaused) {
                $this->_iterateWhilePaused();

                $sleepTime = static::PAUSED_SLEEP_TIME;

                if(!$this->_isStopping && $sleepTime > 0) {
                    if(is_int($sleepTime) || $sleepTime > 10) {
                        sleep((int)$sleepTime);
                    } else {
                        usleep($sleepTime * 1000000);
                    }
                }
            } else {
                $this->_dispatcher->start();
            }

            if(!$this->cycle()) {
                break;
            }
        }

        $this->_isStopped = true;
        $this->_teardown();

        return $this;
    }


    public function cycle() {
        if(!$this->_isStarted || $this->_isStopped) {
            return false;
        }

        if(extension_loaded('pcntl')) {
            pcntl_signal_dispatch();
        }

        clearstatcache();
        //gc_collect_cycles();

        if($this->_isPaused || $this->_isStopping) {
            return false;
        }

        return true;
    }

    protected function _setup() {}
    protected function _iterateWhilePaused() {}
    protected function _teardown() {}

    public function isStarted() {
        return $this->_isStarted;
    }

    public function stop() {
        if(!$this->_isStarted) {
            return $this;
        }

        $this->_isStopping = true;
        return $this;
    }

    public function isStopped() {
        return $this->_isStopped;
    }

    public function pause() {
        $this->_isPaused = true;
        return $this;
    }

    public function resume() {
        $this->_isPaused = false;
        return $this;
    }

    public function isPaused() {
        return $this->_isPaused;
    }


// Stubs
    protected function _getPidFilePath() {
        $appPath = df\Launchpad::$applicationPath;
        $appId = basename($appPath);
        $appId = basename(dirname($appPath)).'-'.$appId;
        $appId = core\string\Manipulator::formatFilename($appId).'-'.df\Launchpad::getActiveApplication()->getUniquePrefix();
        $daemonId = core\string\Manipulator::formatFilename($this->getName());

        return '/var/run/df/'.$appId.'/'.$daemonId.'.pid';
    }

    protected function _preparePrivilegedResources() {}

    protected function _getDaemonUser() {
        return core\Environment::getInstance()->getDaemonUser();
    }

    protected function _getDaemonGroup() {
        return core\Environment::getInstance()->getDaemonGroup();
    }
}