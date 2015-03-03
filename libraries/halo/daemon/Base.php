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
    const TEST_MODE = true;
    const REPORT_STATUS = true;
    const DEV_RUN_TIME = '3 minutes';
    const AUTOMATIC = false;

    public $terminal;
    public $process;

    protected $_isRunning = false;
    protected $_isPaused = false;
    protected $_isStopping = false;
    protected $_isStopped = false;
    protected $_isRestarting = false;
    protected $_isForked = false;

    protected $_startTime;
    protected $_endTime;
    private $_statusPath;

    protected $_user;
    protected $_group;

    public static function launch($name, $environmentMode=null, $user=null) {
        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }
    
        if($user === null) {
            $user = core\Environment::getInstance()->getDaemonUser();
        }

        $path = df\Launchpad::$applicationPath.'/entry/';
        $path .= df\Launchpad::$environmentId.'.'.$environmentMode.'.php';

        return halo\process\Base::launchScript($path, ['daemon', $name], $user);
    }

    public static function loadAll() {
        foreach(df\Launchpad::$loader->lookupClassList('apex/daemons') as $name => $class) {
            try {
                $daemon = self::factory($name);
            } catch(InvalidArgumentException $e) {
                continue;
            }
            
            $output[$name] = $daemon;
        }
        
        ksort($output);
        return $output;
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

    public function setUser($user) {
        $this->_user = $user;
        return $this;
    }

    public function getUser() {
        if(!$this->_user) {
            $this->_user = core\Environment::getInstance()->getDaemonUser();
        }

        return $this->_user;
    }

    public function setGroup($group) {
        $this->_group = $group;
        return $this;
    }

    public function getGroup() {
        if(!$this->_group) {
            $this->_group = core\Environment::getInstance()->getDaemonGroup();
        }

        return $this->_group;
    }


// Runtime
    final public function run() {
        if($this->_isRunning || $this->_isStopping || $this->_isStopped) {
            throw new LogicException(
                'Daemon '.$this->getName().' has already been run'
            );  
        }

        gc_enable();
        $this->context = new core\SharedContext();
        $this->process = halo\process\Base::getCurrent();

        $basePath = df\Launchpad::$application->getLocalStoragePath().'/daemons/'.core\string\Manipulator::formatFileName($this->getName());
        core\io\Util::ensureDirExists(dirname($basePath));

        $this->_startTime = time();
        $this->_statusPath = $basePath.'.status';

        if(!df\Launchpad::$application->isProduction()) {
            $this->_endTime = core\time\Date::factory('+'.self::DEV_RUN_TIME)->toTimestamp();
        }


        $this->io = new core\io\Multiplexer(null, $this->getName());

        if(static::TEST_MODE) {
            $this->io->addChannel(new core\io\channel\Std());
        }

        $this->io->addChannel(new core\io\channel\Stream(fopen($basePath.'.log', 'w')));

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

        $user = $this->getUser();
        $group = $this->getGroup();

        if($isPrivileged) {
            $this->_preparePrivilegedResources();
            $this->process->setIdentity($user, $group);
        } else {
            if($user != $this->process->getOwnerName()) {
                $this->io->writeErrorLine('You are trying to run this daemon as a user with conflicting permissions - either run it as '.$user.' or with sudo!');
                return;
            }
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

                if(($this->_endTime !== null) && (time() > $this->_endTime)) {
                    $this->stop();
                }

                $this->onCycle();
            })
            ->bindTimer('__housekeeping', 30, function() {
                //clearstatcache();
                gc_collect_cycles();
                $this->_reportStatus();
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
            'endTime' => $this->_endTime,
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
}