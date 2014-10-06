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
    
    public $io;
    protected $_statusData;
    
// Execute
    public function dispatch() {
        $this->_beginDispatch();

        if(php_sapi_name() != 'cli') {
            throw new \Exception(
                'Daemon processes must only be started from the CLI SAPI'
            );
        }

        $this->io = new core\io\channel\Std();
        $env = core\Environment::getInstance();

        if(!$env->canUseDaemons() || !extension_loaded('pcntl')) {
            $this->io->writeErrorLine('Daemons are not enabled in config');            
            return;
        }

        $args = core\cli\Command::fromArgv();

        if(!$arg = $args[2]) {
            throw new core\InvalidArgumentException(
                'No daemon path has been specified'
            );
        }

        try {
            $daemon = halo\daemon\Base::factory($arg->toString());
        } catch(\Exception $e) {
            $this->io->writeErrorLine($e->getMessage());
            return;
        }


        $remote = halo\daemon\Remote::factory($daemon);
        $process = $remote->getProcess();
        $name = $daemon->getName();
        $command = (string)$args[3];

        switch($command) {
            case '__spawn':
                return $this->spawn($daemon, $process);

            case '':
            case 'start':
                return $this->start($daemon, $process);

            case 'stop':
                return $this->stop($daemon, $process);

            case 'restart':
                $this->stop($daemon, $process);
                $this->start($daemon);
                return;

            case 'pause':
                $this->io->writeLine('Pausing daemon '.$name);
                $process->sendSignal('SIGTSTP');
                return;

            case 'resume':
                $this->io->writeLine('Resuming daemon '.$name);
                $process->sendSignal('SIGCONT');
                return;

            case 'status':
                return $this->status($daemon, $process);

            case 'nudge':
                return $this->nudge($daemon, $process);
        }
    }

    public function spawn(halo\daemon\IDaemon $daemon, halo\process\IManagedProcess $process=null) {
        if($process) {
            return;
        }

        $daemon->run();
        return;
    }

    public function start(halo\daemon\IDaemon $daemon, halo\process\IManagedProcess $process=null) {
        $name = $daemon->getName();

        if($process) {
            $this->io->writeErrorLine('Daemon '.$name.' is already running');
            return;
        }

        $this->io->write('Starting daemon '.$name);

        if($daemon::TEST_MODE) {
            $this->io->writeLine();
        } else {
            $this->io->write('...');
        }

        $environmentMode = df\Launchpad::getEnvironmentMode();
        $path = df\Launchpad::$applicationPath.'/entry/';
        $path .= df\Launchpad::$environmentId.'.'.$environmentMode.'.php';

        halo\process\Base::launchBackgroundScript($path, ['daemon', $name, '__spawn']);

        $remote = halo\daemon\Remote::factory($daemon);
        $count = 0;

        while(!$process = $remote->getProcess()) {
            if(++$count > 10) {
                break;
            }

            usleep(500000);
            $remote->refresh();
        }

        if($process) {
            $this->io->writeLine(' running with PID: '.$process->getProcessId());
        } else {
            $this->io->writeLine(' done, but PID could not be found!');
        }

        return;
    }

    public function stop(halo\daemon\IDaemon $daemon, halo\process\IManagedProcess $process=null) {
        $name = $daemon->getName();
        
        if(!$process) {
            $this->io->writeLine('Daemon '.$name.' is not running');
            return;
        }

        $this->io->write('Stopping daemon '.$name.'...');
        $process->sendSignal('SIGTERM');
        $count = 0;

        while($process->isAlive()) {
            if($count++ > 10) {
                $this->io->writeLine(' TERM failed, trying KILL...');
                $process->sendSignal('SIGKILL');
                sleep(5);
                break;
            }

            sleep(1);
        }

        if($process->isAlive()) {
            $this->io->writeLine(' still running, not sure what to do now!');
        } else {
            $this->io->writeLine(' done');
        }
    }

    public function status(halo\daemon\IDaemon $daemon, halo\process\IManagedProcess $process=null) {
        $name = $daemon->getName();

        if(!$process) {
            $this->io->writeLine('Daemon '.$name.' is not currently running');
            return;
        }


        if(!$this->_statusData) {
            $this->io->writeLine('Daemon '.$name.' is currently running with PID '.$process->getProcessId());
            return;
        }

        if(isset($this->_statusData['state'])) {
            $state = $this->_statusData['state'];
            unset($this->_statusData['state']);
        }

        $this->io->writeLine('Daemon '.$name.' is currently '.$state);

        foreach($this->_statusData as $key => $value) {
            if(substr($key, -4) == 'Time') {
                $value = (new core\time\Date($value))->localeFormat();
            }

            $this->io->writeLine(core\string\Manipulator::formatName($key).': '.$value);
        }
    }

    public function nudge(halo\daemon\IDaemon $daemon, halo\process\IManagedProcess $process=null) {
        $name = $daemon->getName();
        $this->io->write('Checking daemon '.$name.'...');

        if(!$process) {
            $this->io->writeLine(' not running');
            return $this->start($daemon, $process);
        }

        if(isset($this->_statusData['statusTime'])) {
            if(time() - $this->_statusData['statusTime'] > self::THRESHOLD) {
                // Has it got stuck?
                $this->io->writeLine();
                $this->io->write('Status is stale, restarting...');
                
                $process->kill();
                $process = halo\daemon\Base::launch('TaskSpool');
            }
        }

        $this->io->writeLine(' running with PID: '.$process->getProcessId());
    }
}
