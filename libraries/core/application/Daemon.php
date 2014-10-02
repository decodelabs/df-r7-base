<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;
use df\halo;
use df\flex;

class Daemon extends Base {
    
    const RUN_MODE = 'Daemon';
    
    public $terminal;
    
// Execute
    public function dispatch() {
        $this->_beginDispatch();

        if(php_sapi_name() != 'cli') {
            throw new \Exception(
                'Daemon processes must only be started from the CLI SAPI'
            );
        }

        $this->terminal = new core\io\channel\Std();
        $args = core\cli\Command::fromArgv();

        if(!$arg = $args[2]) {
            throw new core\InvalidArgumentException(
                'No daemon path has been specified'
            );
        }

        try {
            $daemon = halo\daemon\Base::factory($arg->toString());
        } catch(\Exception $e) {
            $this->terminal->writeErrorLine($e->getMessage());
            return;
        }

        $name = $daemon->getName();
        $command = (string)$args[3];
        $process = $this->_getDaemonProcess($daemon);

        switch($command) {
            case 'run':
            case 'start':
            case '':
                return $this->start($daemon, $process);

            case 'stop':
                return $this->stop($daemon, $process);

            case 'restart':
                $this->stop($daemon, $process);
                $this->start($daemon);
                return;

            case 'pause':
                $this->terminal->writeLine('Pausing daemon '.$name);
                $process->sendSignal('SIGTSTP');
                return;

            case 'resume':
                $this->terminal->writeLine('Resuming daemon '.$name);
                $process->sendSignal('SIGCONT');
                return;
        }
    }

    public function start(halo\daemon\IDaemon $daemon, halo\process\IManagedProcess $process=null) {
        $name = $daemon->getName();

        if($process) {
            $this->terminal->writeErrorLine('Daemon '.$name.' is already running');
            return;
        }

        $this->terminal->write('Starting daemon '.$name);

        if($daemon::TEST_MODE) {
            $this->terminal->writeLine();
        } else {
            $this->terminal->write('...');
        }

        if($daemon->run()) {
            if(!$daemon::TEST_MODE) {
                $this->terminal->writeLine(' done');
            }
        }

        return;
    }

    public function stop(halo\daemon\IDaemon $daemon, halo\process\IManagedProcess $process=null) {
        $name = $daemon->getName();
        
        if(!$process) {
            $this->terminal->writeLine('Daemon '.$name.' is not running');
            return;
        }

        $this->terminal->write('Stopping daemon '.$name.'...');
        $process->sendSignal('SIGTERM');
        $count = 0;

        while($process->isAlive()) {
            if($count++ > 10) {
                $this->terminal->writeLine(' TERM failed, trying KILL...');
                $process->sendSignal('SIGKILL');
                sleep(5);
                break;
            }

            sleep(1);
        }

        if($process->isAlive()) {
            $this->terminal->writeLine(' still running, not sure what to do now!');
        } else {
            $this->terminal->writeLine(' done');
        }
    }

    protected function _getDaemonProcess(halo\daemon\IDaemon $daemon) {
        $name = $daemon->getName();
        $pid = null;

        if($daemon::REPORT_STATUS) {
            $path = $this->getLocalStoragePath().'/daemons/'.core\string\Manipulator::formatFileName($name).'.status';

            if(!is_file($path)) {
                return null;
            }

            $data = flex\json\Codec::decode(file_get_contents($path));

            if(isset($data['pid'])) {
                $pid = $data['pid'];
            }
        }

        if(!$pid) {
            $pidPath = $daemon->getPidFilePath();

            if(is_file($pidPath)) {
                $pid = file_get_contents($pidPath);
            } else {
                return null;
            }
        }

        $process = halo\process\Base::fromPid($pid);

        if(!$process->isAlive()) {
            return null;
        }

        return $process;
    }
}
