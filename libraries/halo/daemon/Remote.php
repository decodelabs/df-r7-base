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

class Remote implements IRemote {
    
    protected $_daemon;
    protected $_process;
    protected $_statusData;
    protected $_isChecked = false;

    public static function factory($daemon) {
        if(!$daemon instanceof IDaemon) {
            $daemon = Base::factory($daemon);
        }

        return new self($daemon);
    }

    protected function __construct(IDaemon $daemon) {
        $this->_daemon = $daemon;
    }

    public function getName() {
        return $this->_daemon->getName();
    }

    public function isRunning() {
        if(!$this->_isChecked) {
            $this->refresh();
        }

        return $this->_process !== null;
    }

    public function getStatusData() {
        if(!$this->_isChecked) {
            $this->refresh();
        }

        return $this->_statusData;
    }

    public function getProcess() {
        if(!$this->_isChecked) {
            $this->refresh();
        }

        return $this->_process;
    }

    public function refresh() {
        $this->_isChecked = true;
        clearstatcache();
        $this->_statusData = null;
        $this->_process = null;
        $daemon = $this->_daemon;

        $name = $daemon->getName();
        $pid = null;

        if($daemon::REPORT_STATUS) {
            $path = df\Launchpad::$application->getLocalStoragePath().'/daemons/'.core\string\Manipulator::formatFileName($name).'.status';

            if(!is_file($path)) {
                return $this;
            }

            $this->_statusData = flex\json\Codec::decode(file_get_contents($path));

            if(isset($this->_statusData['pid'])) {
                $pid = $this->_statusData['pid'];
            }
        }

        if(!$pid) {
            $pidPath = $daemon->getPidFilePath();

            if(is_file($pidPath)) {
                $pid = file_get_contents($pidPath);
            } else {
                return $this;
            }
        }

        $this->_process = halo\process\Base::fromPid($pid);

        if(!$this->_process->isAlive()) {
            $this->_process = null;
        }

        return $this;
    }




    public function start() {
        return $this->_sendCommand('start');
    }

    public function stop() {
        return $this->_sendCommand('stop');
    }

    public function restart() {
        return $this->_sendCommand('restart');
    }

    public function pause() {
        return $this->_sendCommand('pause');
    }

    public function resume() {
        return $this->_sendCommand('resume');
    }

    protected function _sendCommand($command) {
        $environmentMode = df\Launchpad::getEnvironmentMode();
        $path = df\Launchpad::$applicationPath.'/entry/';
        $path .= df\Launchpad::$environmentId.'.'.$environmentMode.'.php';

        return halo\process\Base::launchBackgroundScript($path, ['daemon', $this->_daemon->getName(), $command]);
    }
}