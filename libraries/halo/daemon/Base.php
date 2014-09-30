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
        $this->_isStarted = true;

        $this->_setup();

        $this->_dispatcher
            ->setCycleHandler(function() {
                if(!$this->_isStarted 
                || $this->_isPaused 
                || $this->_isStopping
                || $this->_isStopped) {
                    return false;
                }

                clearstatcache();
                //gc_collect_cycles();

                if(method_exists($this, 'onCycle')) {
                    $this->onCycle();
                }
            })
            ->setSignalHandler(['SIGHUP'], function() {})
            ->setSignalHandler(['SIGTERM', 'SIGINT'], [$this, 'stop'])
            ->setSignalHandler(['SIGTSTP'], [$this, 'pause'])
            ->setSignalHandler(['SIGCONT'], [$this, 'resume']);

        if(method_exists($this, 'onTerminalInput')
        && ($std = $this->terminal->getChannel('STD'))) {
            $this->_dispatcher->newStreamHandler($std->getInputStream())
                ->bindPersistent(function() use($std) {
                    $this->onTerminalInput(rtrim($std->readLine(), "\r\n"));
                }, 'terminalInput');
        }


        $pauseDispatcher = (new halo\event\select\Dispatcher())
            ->setCycleHandler(function() {
                if(!$this->_isStarted 
                || !$this->_isPaused 
                || $this->_isStopping
                || $this->_isStopped) {
                    return false;
                }

                if(method_exists($this, 'onCycle')) {
                    $this->onCycle();
                }
            })
            ->setSignalHandler(['SIGHUP'], function() {})
            ->setSignalHandler(['SIGTERM', 'SIGINT'], [$this, 'stop'])
            ->setSignalHandler(['SIGTSTP'], [$this, 'pause'])
            ->setSignalHandler(['SIGCONT'], [$this, 'resume']);


        while(true) {
            $this->_dispatcher->start();

            if($this->_isPaused) {
                $pauseDispatcher->start();
            } else {
                break;
            }
        }

        $this->_isStopped = true;
        $this->_teardown();

        return $this;
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
        $this->terminal->writeLine();
        $this->terminal->writeLine('** STOPPING **');
        return $this;
    }

    public function isStopped() {
        return $this->_isStopped;
    }

    public function pause() {
        $this->_isPaused = true;
        $this->terminal->writeLine('** PAUSED **');
        return $this;
    }

    public function resume() {
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