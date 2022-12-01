<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\halo\daemon;

use DecodeLabs\Atlas;
use DecodeLabs\Deliverance;

use DecodeLabs\Dictum;
use DecodeLabs\Eventful\Dispatcher as EventDispatcher;
use DecodeLabs\Eventful\Dispatcher\Select as SelectDispatcher;
use DecodeLabs\Eventful\Factory as EventFactory;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Systemic;
use DecodeLabs\Terminus as Cli;
use df\core;
use df\flex;

abstract class Base implements IDaemon
{
    use core\TContextProxy;

    public const REQUIRES_PRIVILEGED_PROCESS = false;
    public const TEST_MODE = false;
    public const REPORT_STATUS = true;
    public const DEV_RUN_TIME = '3 minutes';
    public const AUTOMATIC = false;

    public $terminal;
    public $process;
    public $events;

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

    public static function launch($name, $user = null)
    {
        if ($user === null) {
            $user = core\environment\Config::getInstance()->getDaemonUser();
        }

        return Systemic::scriptCommand([
                Legacy::getEntryFile(), 'daemon', $name
            ])
            ->setUser($user)
            ->run();
    }

    public static function loadAll()
    {
        $output = [];

        foreach (Legacy::getLoader()->lookupClassList('apex/daemons') as $name => $class) {
            try {
                $daemon = self::factory($name);
            } catch (NotFoundException $e) {
                continue;
            }

            $output[$name] = $daemon;
        }

        ksort($output);
        return $output;
    }

    public static function factory($name)
    {
        $parts = explode('/', $name);
        $top = ucfirst((string)array_pop($parts));
        $parts[] = $top;
        $class = 'df\\apex\\daemons\\' . implode('\\', $parts);

        if (!class_exists($class)) {
            throw Exceptional::NotFound(
                'Daemon ' . $name . ' could not be found'
            );
        }

        return new $class();
    }

    protected function __construct()
    {
    }

    public function getName(): string
    {
        $parts = array_slice(explode('\\', get_class($this)), 3);
        return implode('/', $parts);
    }

    public function setUser($user)
    {
        $this->_user = $user;
        return $this;
    }

    public function getUser()
    {
        if (!$this->_user) {
            $this->_user = core\environment\Config::getInstance()->getDaemonUser();
        }

        return $this->_user;
    }

    public function setGroup($group)
    {
        $this->_group = $group;
        return $this;
    }

    public function getGroup()
    {
        if (!$this->_group) {
            $this->_group = core\environment\Config::getInstance()->getDaemonGroup();
        }

        return $this->_group;
    }


    public function isTesting(): bool
    {
        return static::TEST_MODE;
    }


    // Runtime
    final public function run()
    {
        if ($this->_isRunning || $this->_isStopping || $this->_isStopped) {
            throw Exceptional::Logic(
                'Daemon ' . $this->getName() . ' has already been run'
            );
        }

        gc_enable();
        $this->context = new core\SharedContext();
        $this->process = Systemic::getCurrentProcess();

        $basePath = Genesis::$hub->getLocalDataPath() . '/daemons/' . Dictum::fileName($this->getName());
        Atlas::createDir(dirname($basePath));

        $this->_startTime = time();
        $this->_statusPath = $basePath . '.status';

        if (!Genesis::$environment->isProduction()) {
            $this->_endTime = core\time\Date::factory('+' . self::DEV_RUN_TIME)->toTimestamp();
        }


        if (static::TEST_MODE) {
            $broker = Deliverance::newCliBroker();
        } else {
            $broker = Deliverance::newBroker()
                ->addOutputReceiver(Atlas::file($basePath . '.log', 'w'));
        }

        Cli::setSession(
            Cli::newSession(
                Cli::newRequest(),
                $broker
            )
        );

        $isPrivileged = $this->process->isPrivileged();

        if (!$isPrivileged && static::REQUIRES_PRIVILEGED_PROCESS) {
            throw Exceptional::Runtime(
                'Daemon ' . $this->getName() . ' must be running from a privileged process'
            );
        }

        if (!static::TEST_MODE && $this->process->canFork()) {
            if ($this->process->fork()) {
                return true;
            } else {
                $this->_isForked = true;
            }
        }

        try {
            $this->_runForked();
        } catch (\Throwable $e) {
            file_put_contents(Genesis::$hub->getApplicationPath() . '/daemon-error', (string)$e);
            throw $e;
        }
    }

    protected function newDispatcher(): EventDispatcher
    {
        //return EventFactory::newDispatcher();
        return new SelectDispatcher();
    }

    private function _runForked()
    {
        fclose(\STDIN);
        fclose(\STDOUT);
        fclose(\STDERR);
        $this->events = $this->newDispatcher();

        $pidPath = $this->getPidFilePath();

        if ($pidPath) {
            try {
                $this->process->setPidFilePath($pidPath);
            } catch (\Throwable $e) {
                Cli::error($e->getMessage());
                return;
            }
        }



        $user = $this->getUser();
        $group = $this->getGroup();
        $isPrivileged = $this->process->isPrivileged();

        if ($isPrivileged) {
            $this->_preparePrivilegedResources();
            $this->process->setIdentity($user, $group);
        } else {
            //if ($user != $this->process->getOwnerName()) {
            //Cli::error('You are trying to run this daemon as a user with conflicting permissions - either run it as ' . $user . ' or with sudo!');
            //return;
            //}
        }


        declare(ticks=20);
        $this->_isRunning = true;

        $this->_setup();

        $this->_setupDefaultEvents($this->events);
        $pauseEvents = $this->_setupDefaultEvents($this->newDispatcher(), true);

        while (true) {
            if ($this->_isStopping) {
                break;
            }

            if (static::REPORT_STATUS) {
                $this->_reportStatus();
            }

            if ($this->_isPaused) {
                $pauseEvents->listen();
            } else {
                $this->events->listen();

                /** @phpstan-ignore-next-line */
                if (!$this->_isPaused) {
                    break;
                }
            }
        }

        $this->_isStopped = true;
        $this->_teardown();

        if ($pidPath) {
            Atlas::deleteFile($pidPath);
        }

        if (static::REPORT_STATUS) {
            Atlas::deleteFile($this->_statusPath);
        }

        if ($this->_isRestarting) {
            self::launch($this->getName());
        }
    }

    protected function _setupDefaultEvents(EventDispatcher $events, $pauseEvents = false)
    {
        $events
            ->setCycleHandler(function () use ($pauseEvents) {
                if (!$this->_isRunning
                || ($pauseEvents && !$this->_isPaused)
                || (!$pauseEvents && $this->_isPaused)
                || $this->_isStopping
                || $this->_isStopped) {
                    return false;
                }

                if (($this->_endTime !== null) && (time() > $this->_endTime)) {
                    $this->stop();
                }

                $this->onCycle();
            })
            ->bindTimer('__housekeeping', 30, function () {
                //clearstatcache();
                gc_collect_cycles();
                $this->_reportStatus();
            })
            ->bindSignal('hangup', ['SIGHUP'], function () {
            })
            ->bindSignal('stop', ['SIGTERM', 'SIGINT'], [$this, 'stop'])
            ->bindSignal('pause', ['SIGTSTP'], [$this, 'pause'])
            ->bindSignal('resume', ['SIGCONT'], [$this, 'resume'])
            ->bindSignal('restart', ['SIGABRT'], [$this, 'restart'])
        ;

        return $events;
    }

    public function getPidFilePath()
    {
        return Genesis::$hub->getLocalDataPath() . '/daemons/' . Dictum::fileName($this->getName()) . '.pid';
    }

    protected function _setup()
    {
    }
    protected function _teardown()
    {
    }

    public function onCycle()
    {
    }
    public function onTerminalInput($line)
    {
    }


    protected function _reportStatus()
    {
        if (!static::REPORT_STATUS) {
            return;
        }

        Atlas::createDir(dirname($this->_statusPath));

        $state = 'running';

        if ($this->_isPaused) {
            $state = 'paused';
        } elseif ($this->_isStopping) {
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

        if (is_array($data) && !empty($data)) {
            $status = array_merge($data, $status);
        }

        file_put_contents($this->_statusPath, flex\Json::toString($status));
    }

    protected function _getStatusData()
    {
    }

    public function isRunning()
    {
        return $this->_isRunning;
    }

    public function stop()
    {
        if (!$this->_isRunning || $this->_isStopping || $this->_isStopped) {
            return $this;
        }

        //Cli::info('STOPPING');

        $this->_isStopping = true;
        $this->events->stop();
        return $this;
    }

    public function isStopped()
    {
        return $this->_isStopped;
    }

    public function pause()
    {
        if (!$this->_isRunning || $this->_isPaused || $this->_isStopping || $this->_isStopped) {
            return $this;
        }

        $this->_isPaused = true;
        //Cli::info('PAUSED');
        return $this;
    }

    public function resume()
    {
        if (!$this->_isRunning || !$this->_isPaused || $this->_isStopping || $this->_isStopped) {
            return $this;
        }

        $this->_isPaused = false;
        //Cli::info('RESUMING');
        return $this;
    }

    public function isPaused()
    {
        return $this->_isPaused;
    }

    public function restart()
    {
        $this->stop();
        $this->_isRestarting = true;
        return $this;
    }


    // Stubs
    protected function _preparePrivilegedResources()
    {
    }
}
