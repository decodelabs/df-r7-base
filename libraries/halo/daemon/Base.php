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

    const DEFAULT_SLEEP_TIME = 2;
    const PAUSED_SLEEP_TIME = 4;
    const REQUIRES_PRIVILEGED_PROCESS = true;

    private static $_signalIndex = null;

    protected $_signalHandlers = [
        'SIGHUP' => null,
        'SIGINT' => null,
        'SIGQUIT' => null,
        'SIGILL' => null,
        'SIGTRAP' => null,
        'SIGABRT' => null,
        'SIGIOT' => null,
        'SIGBUS' => null,
        'SIGFPE' => null,
        'SIGUSR1' => null,
        'SIGSEGV' => null,
        'SIGUSR2' => null,
        'SIGALRM' => null,
        'SIGTERM' => null,
        'SIGSTKFLT' => null,
        'SIGCLD' => null,
        'SIGCHLD' => null,
        'SIGCONT' => null,
        'SIGTSTP' => null,
        'SIGTTIN' => null,
        'SIGTTOU' => null,
        'SIGURG' => null,
        'SIGXCPU' => null,
        'SIGXFSZ' => null,
        'SIGVTALRM' => null,
        'SIGPROF' => null,
        'SIGWINCH' => null,
        'SIGPOLL' => null,
        'SIGIO' => null,
        'SIGPWR' => null,
        'SIGSYS' => null,
        'SIGBABY' => null
    ];

    protected $_process;
    protected $_isStarted = false;
    protected $_isStopping = false;
    protected $_isStopped = false;
    protected $_isPaused = false;
    protected $_isForked = false;
    protected $_sleepTime = self::DEFAULT_SLEEP_TIME;

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

// Sleep
    public function setSleepTime($time) {
        if($time === null) {
            $time = self::DEFAULT_SLEEP_TIME;
        }

        $this->_sleepTime = (float)$time;
        return $this;
    }

    public function getSleepTime() {
        return $this->_sleepTime;
    }

// Signals
    public function registerSignalHandler($signals, Callable $handler) {
        if(!extension_loaded('pcntl')) {
            return $this;
        }

        if(!is_array($signal)) {
            $signals = (array)$signals;
        }

        $index = null;

        foreach($signals as $signal) {
            if($signal = halo\process\Signal::normalizeSignalName($signal)) {
                $this->_signalHandlers[$signal] = $handler;

                if($this->_isStarted) {
                    pcntl_signal(constant($signal), $handler);
                }
            }
        }

        return $this;
    }

    public function hasSignalHandler($signal) {
        try {
            return isset($this->_signalHandlers[halo\process\Signal::normalizeSignalName($signal)]);
        } catch(\Exception $e) {
            return false;
        }
    }

    public function _defaultSignalHandler($signalNo) {
        switch($signalNo) {
            case SIGKILL:
                exit;

            case SIGTERM:
            case SIGINT:
                // Shutdown
                $this->stop();
                break;

            case SIGTSTP:
            case SIGSTOP:
                // Pause
                $this->pause();
                break;

            case SIGCONT:
                // Continue
                $this->resume();
                break;

            default:
                // Handle all other signals
                break;
        }
    }


// Runtime
    final public function start() {
        if($this->_isStarted) {
            throw new LogicException(
                'Daemon '.$this->getName().' is already running'
            );  
        }

        gc_enable();
        
        $process = halo\process\Base::getCurrent();
        $system = halo\system\Base::getInstance();
        $isPrivileged = $process->isPrivileged();

        if(!$isPrivileged && static::REQUIRES_PRIVILEGED_PROCESS) {
            throw new RuntimeException(
                'Daemon '.$this->getName().' must be running from a privileged process'
            );
        }

        if($process->canFork()) {
            if($process->fork()) {
                exit;
            } else {
                $this->_isForked = true;
            }
        }

        $process->setTitle(df\Launchpad::$application->getName().' - '.$this->getName());

        if($isPrivileged) {
            if($system->getPlatformType() == 'Unix') {
                $pidPath = $this->_getPidFilePath();

                if($pidPath) {
                    $process->setPidFilePath($pidPath);
                }
            }

            $this->_preparePrivilegedResources();

            $user = $this->_getDaemonUser();
            $group = $this->_getDaemonGroup();

            $process->setIdentity($user, $group);
        }

        declare(ticks = 1);
        $this->_isStarted = true;

        $this->_setup();

        if(extension_loaded('pcntl')) {
            foreach($this->_signalHandlers as $signal => $handler) {
                if(!defined($signal)) {
                    continue;
                }

                if($handler === null) {
                    $handler = [$this, '_defaultSignalHandler'];
                }

                pcntl_signal(constant($signal), $handler);
            }
        }

        while(true) {
            if($this->_isPaused) {
                $this->_iterateWhilePaused();
            } else {
                $this->_iterate();
            }

            if(!$this->cycle()) {
                break;
            }
        }

        $this->_isStopped = true;
        $this->_teardown();

        // Remove signal handlers
        if(extension_loaded('pcntl')) {
            foreach($this->_signalHandlers as $signal => $handler) {
                if(!defined($signal)) {
                    continue;
                }

                pcntl_signal(constant($signal), function(){});
            }
        }

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

        if($this->_isPaused) {
            $sleepTime = static::PAUSED_SLEEP_TIME;
        } else {
            $sleepTime = $this->_sleepTime;
        }

        if(!$this->_isStopping && $sleepTime > 0) {
            if(is_int($sleepTime) || $sleepTime > 10) {
                sleep((int)$sleepTime);
            } else {
                usleep($sleepTime * 1000000);
            }
        }

        if($this->_isPaused || $this->_isStopping) {
            return false;
        }

        return true;
    }

    protected function _setup() {}
    abstract protected function _iterate();
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