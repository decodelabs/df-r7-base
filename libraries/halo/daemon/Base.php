<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\daemon;

use df;
use df\core;
use df\halo;
use df\flex;
    
abstract class Base implements IDaemon {

    use halo\event\TDispatcherProvider;
    use core\TContextProxy;

    const REQUIRES_PRIVILEGED_PROCESS = false;
    const TEST_MODE = false;
    const REPORT_STATUS = true;

    public $terminal;
    public $process;

    protected $_isRunning = false;
    protected $_isPaused = false;
    protected $_isStopping = false;
    protected $_isStopped = false;
    protected $_isRestarting = false;
    protected $_isForked = false;

    protected $_startTime;
    private $_statusPath;

    public static function launch($name, $environmentMode=null) {
        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }
        
        $path = df\Launchpad::$applicationPath.'/entry/';
        $path .= df\Launchpad::$environmentId.'.'.$environmentMode.'.php';

        return halo\process\Base::launchBackgroundScript($path, ['daemon', $name]);
    }

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
        $this->_context = new core\SharedContext();
        $this->process = halo\process\Base::getCurrent();

        $basePath = df\Launchpad::$application->getLocalStoragePath().'/daemons/'.core\string\Manipulator::formatFileName($this->getName());
        core\io\Util::ensureDirExists(dirname($basePath));

        $this->_startTime = time();
        $this->_statusPath = $basePath.'.status';

        $this->io = new core\io\Multiplexer(null, $this->getName());

        if(static::TEST_MODE) {
            $this->io->addChannel(new core\io\channel\Std());
        } else {
            $this->io->addChannel(new core\io\channel\Stream(fopen($basePath.'.log', 'w')));
        }

        $system = halo\system\Base::getInstance();
        $isPrivileged = $this->process->isPrivileged();

        if(!$isPrivileged && static::REQUIRES_PRIVILEGED_PROCESS) {
            throw new RuntimeException(
                'Daemon '.$this->getName().' must be running from a privileged process'
            );
        }

        if(!static::TEST_MODE && $this->process->canFork()) {
            if($this->process->fork()) {
                return true;
                //exit;
            } else {
                $this->_isForked = true;
            }
        }

        $this->getEventDispatcher();
        $this->process->setTitle(df\Launchpad::$application->getName().' - '.$this->getName());
            
        $pidPath = $this->getPidFilePath();

        if($pidPath) {
            try {
                $this->process->setPidFilePath($pidPath);
            } catch(\Exception $e) {
                $this->io->writeErrorLine($e->getMessage());
                return;
            }
        }

        if($isPrivileged) {
            $this->_preparePrivilegedResources();

            $user = $this->_getDaemonUser();
            $group = $this->_getDaemonGroup();

            $this->process->setIdentity($user, $group);
        }

        declare(ticks = 20);
        $this->_isRunning = true;

        $this->_setup();

        $this->_setupDefaultEvents($this->events);
        $pauseEvents = $this->_setupDefaultEvents(new halo\event\Select(), true);

        while(true) {
            if($this->_isStopping) {
                break;
            }

            if(static::REPORT_STATUS) {
                $this->_reportStatus();
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

        if($pidPath) {
            core\io\Util::deleteFile($pidPath);
        }

        if(static::REPORT_STATUS) {
            core\io\Util::deleteFile($this->_statusPath);
        }

        if($this->_isRestarting) {
            self::launch($this->getName());
        }
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
            ->bindTimer('__housekeeping', 30, function() {
                //clearstatcache();
                gc_collect_cycles();
                $this->_reportStatus();
            })
            ->bindStreamRead(core\io\channel\Std::getInputStream(), function($std) use($pauseEvents) {
                $line = rtrim($std->readLine(), "\r\n");

                if($pauseEvents) {
                    switch(trim($line)) {
                        case 'resume':
                            $this->resume();
                            break;

                        case 'stop':
                            $this->stop();
                            break;
                    }
                } else {
                    $this->onTerminalInput($line);
                }
            })
            ->bindSignal('hangup', ['SIGHUP'], function() {})
            ->bindSignal('stop', ['SIGTERM', 'SIGINT'], [$this, 'stop'])
            ->bindSignal('pause', ['SIGTSTP'], [$this, 'pause'])
            ->bindSignal('resume', ['SIGCONT'], [$this, 'resume'])
            ->bindSignal('restart', ['SIGABRT'], [$this, 'restart'])
            ;

        return $dispatcher;
    }

    public function getPidFilePath() {
        return df\Launchpad::$application->getLocalStoragePath().'/daemons/'.core\string\Manipulator::formatFileName($this->getName()).'.pid';
    }

    protected function _setup() {}
    protected function _teardown() {}

    public function onCycle() {}
    public function onTerminalInput($line) {}


    protected function _reportStatus() {
        if(!static::REPORT_STATUS) {
            return;
        }

        core\io\Util::ensureDirExists(dirname($this->_statusPath));

        $state = 'running';

        if($this->_isPaused) {
            $state = 'paused';
        } else if($this->_isStopping) {
            $state = 'stopping';
        }

        $status = [
            'pid' => $this->process->getProcessId(),
            'state' => $state,
            'startTime' => $this->_startTime,
            'statusTime' => time()
        ];

        $data = $this->_getStatusData();

        if(is_array($data) && !empty($data)) {
            $status = array_merge($data, $status);
        }

        file_put_contents($this->_statusPath, flex\json\Codec::encode($status));
    }

    protected function _getStatusData() {}

    public function isRunning() {
        return $this->_isRunning;
    }

    public function stop() {
        if(!$this->_isRunning || $this->_isStopping || $this->_isStopped) {
            return $this;
        }

        $this->_isStopping = true;
        $this->io->writeLine('** STOPPING **');
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
        $this->io->writeLine('** PAUSED **');
        return $this;
    }

    public function resume() {
        if(!$this->_isRunning || !$this->_isPaused || $this->_isStopping || $this->_isStopped) {
            return $this;
        }

        $this->_isPaused = false;
        $this->io->writeLine('** RESUMING **');
        return $this;
    }

    public function isPaused() {
        return $this->_isPaused;
    }

    public function restart() {
        $this->stop();
        $this->_isRestarting = true;
        return $this;
    }


// Stubs
    protected function _preparePrivilegedResources() {}

    protected function _getDaemonUser() {
        return core\Environment::getInstance()->getDaemonUser();
    }

    protected function _getDaemonGroup() {
        return core\Environment::getInstance()->getDaemonGroup();
    }
}