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

    const REQUIRES_PRIVILEGED_PROCESS = false;
    const FORK_ON_LOAD = true;

    public $terminal;
    public $process;

    protected $_isRunning = false;
    protected $_isPaused = false;
    protected $_isStopping = false;
    protected $_isStopped = false;
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
    final public function run() {
        if($this->_isRunning || $this->_isStopping || $this->_isStopped) {
            throw new LogicException(
                'Daemon '.$this->getName().' has already been run'
            );  
        }

        gc_enable();

        $this->terminal = core\io\Multiplexer::defaultFactory('daemon');
        $this->process = halo\process\Base::getCurrent();
        $system = halo\system\Base::getInstance();
        $isPrivileged = $this->process->isPrivileged();

        if(!$isPrivileged && static::REQUIRES_PRIVILEGED_PROCESS) {
            throw new RuntimeException(
                'Daemon '.$this->getName().' must be running from a privileged process'
            );
        }

        if(static::FORK_ON_LOAD && $this->process->canFork()) {
            if($this->process->fork()) {
                exit;
            } else {
                $this->_isForked = true;
            }
        }

        $this->getDispatcher();
        $this->process->setTitle(df\Launchpad::$application->getName().' - '.$this->getName());

        if($isPrivileged) {
            if($system->getPlatformType() == 'Unix') {
                $pidPath = $this->_getPidFilePath();

                if($pidPath) {
                    $this->process->setPidFilePath($pidPath);
                }
            }

            $this->_preparePrivilegedResources();

            $user = $this->_getDaemonUser();
            $group = $this->_getDaemonGroup();

            $this->process->setIdentity($user, $group);
        }

        declare(ticks = 1);
        $this->_isRunning = true;

        $this->_setup();

        $this->_setupDefaultEvents($this->events);
        $pauseEvents = $this->_setupDefaultEvents(new halo\event\select\Dispatcher(), true);

        while(true) {
            if($this->_isStopping) {
                break;
            }

            if($this->_isPaused) {
                $pauseEvents->listen();
            } else {
                $this->events->listen();

                if(!$this->_isPaused) {
                    break;
                }
            }
        }

        $this->_isStopped = true;
        $this->_teardown();

        return $this;
    }

    protected function _setupDefaultEvents(halo\event\IDispatcher $dispatcher, $pauseEvents=false) {
        $dispatcher
            ->setCycleHandler(function() use($pauseEvents) {
                if(!$this->_isRunning 
                || ($pauseEvents && !$this->_isPaused)
                || (!$pauseEvents && $this->_isPaused)
                || $this->_isStopping
                || $this->_isStopped) {
                    return false;
                }

                $this->onCycle();
            })
            ->bindTimer('__gc', 60, function() {
                clearstatcache();
                gc_collect_cycles();
            })
            ->bindSignal('hangup', ['SIGHUP'], function() {})
            ->bindSignal('stop', ['SIGTERM', 'SIGINT'], [$this, 'stop'])
            ->bindSignal('pause', ['SIGTSTP'], [$this, 'pause'])
            ->bindSignal('resume', ['SIGCONT'], [$this, 'resume']);

        if(!$pauseEvents) {
            if($std = $this->terminal->getChannel('STD')) {
                $dispatcher->bindStreamRead('terminalInput', $std->getInputStream(), function($std) {
                    $this->onTerminalInput(rtrim($std->readLine(), "\r\n"));
                });
            }
        } else {
            if($std = $this->terminal->getChannel('STD')) {
                $dispatcher->bindStreamRead('terminalInput', $std->getInputStream(), function($std) {
                    $line = rtrim($std->readLine(), "\r\n");

                    switch(trim($line)) {
                        case 'resume':
                            $this->resume();
                            break;

                        case 'stop':
                            $this->stop();
                            break;
                    }
                });
            }
        }

        return $dispatcher;
    }

    protected function _setup() {}
    protected function _teardown() {}

    public function onCycle() {}
    public function onTerminalInput($line) {}

    public function isRunning() {
        return $this->_isRunning;
    }

    public function stop() {
        if(!$this->_isRunning || $this->_isStopping || $this->_isStopped) {
            return $this;
        }

        $this->_isStopping = true;
        $this->terminal->writeLine();
        $this->terminal->writeLine('** STOPPING **');
        return $this;
    }

    public function isStopped() {
        return $this->_isStopped;
    }

    public function pause() {
        if(!$this->_isRunning || $this->_isPaused || $this->_isStopping || $this->_isStopped) {
            return $this;
        }

        $this->_isPaused = true;
        $this->terminal->writeLine('** PAUSED **');
        return $this;
    }

    public function resume() {
        if(!$this->_isRunning || !$this->_isPaused || $this->_isStopping || $this->_isStopped) {
            return $this;
        }

        $this->_isPaused = false;
        $this->terminal->writeLine('** RESUMING **');
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
        $appId = core\string\Manipulator::formatFileName($appId).'-'.df\Launchpad::getApplication()->getUniquePrefix();
        $daemonId = core\string\Manipulator::formatFileName($this->getName());

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