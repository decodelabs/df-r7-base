<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\halo\daemon;

use DecodeLabs\Dictum;

use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Systemic;
use DecodeLabs\Systemic\Process;
use DecodeLabs\Terminus\Session;
use df\flex;

class Remote implements IRemote
{
    protected $_daemon;
    protected $_process;
    protected $_statusData;
    protected $_isChecked = false;
    protected $_session;

    public static function factory($daemon)
    {
        if (!$daemon instanceof IDaemon) {
            $daemon = Base::factory($daemon);
        }

        return new self($daemon);
    }

    protected function __construct(IDaemon $daemon)
    {
        $this->_daemon = $daemon;
    }

    public function getName(): string
    {
        return $this->_daemon->getName();
    }


    public function setCliSession(?Session $session)
    {
        $this->_session = $session;
        return $this;
    }

    public function getCliSession(): ?Session
    {
        return $this->_session;
    }


    public function isRunning()
    {
        if (!$this->_isChecked) {
            $this->refresh();
        }

        return $this->_process !== null;
    }

    public function getStatusData()
    {
        if (!$this->_isChecked) {
            $this->refresh();
        }

        return $this->_statusData;
    }

    public function getProcess(): ?Process
    {
        if (!$this->_isChecked) {
            $this->refresh();
        }

        return $this->_process;
    }

    public function refresh()
    {
        $this->_isChecked = true;
        clearstatcache();
        $this->_statusData = null;
        $this->_process = null;
        $daemon = $this->_daemon;

        $name = $daemon->getName();
        $pid = null;

        if ($daemon::REPORT_STATUS) {
            $path = Genesis::$hub->getLocalDataPath() . '/daemons/' . Dictum::fileName($name) . '.status';

            if (!is_file($path)) {
                return $this;
            }

            $this->_statusData = flex\Json::fromFile($path);

            if (isset($this->_statusData['pid'])) {
                $pid = $this->_statusData['pid'];
            }
        }

        if (!$pid) {
            $pidPath = $daemon->getPidFilePath();

            if (is_file($pidPath)) {
                $pid = file_get_contents($pidPath);
            } else {
                return $this;
            }
        }

        $this->_process = Systemic::getProcess((int)$pid);

        if (!$this->_process->isAlive()) {
            $this->_process = null;
        }

        return $this;
    }




    public function start()
    {
        return $this->sendCommand('start');
    }

    public function stop()
    {
        return $this->sendCommand('stop');
    }

    public function restart()
    {
        return $this->sendCommand('restart');
    }

    public function pause()
    {
        return $this->sendCommand('pause');
    }

    public function resume()
    {
        return $this->sendCommand('resume');
    }

    public function nudge()
    {
        return $this->sendCommand('nudge');
    }

    public function sendCommand($command)
    {
        switch ($command) {
            case '__spawn':
            case 'start':
            case 'stop':
            case 'restart':
            case 'pause':
            case 'resume':
            case 'status':
            case 'nudge':
                break;

            default:
                throw Exceptional::InvalidArgument(
                    $command . ' is not a valid command'
                );
        }

        return Systemic::runScript(
            [Legacy::getEntryFile(), 'daemon', $this->_daemon->getName(), $command],
            Genesis::$hub->getApplicationPath()
        );
    }
}
