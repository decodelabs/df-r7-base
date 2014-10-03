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

        if($daemon->run()) {
            if(!$daemon::TEST_MODE) {
                $this->io->writeLine(' done');
            }
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
        $justStarted = false;

        $this->io->write('Checking daemon '.$name.'...');

        if(!$process) {
            $this->io->writeLine(' not running');
            $this->io->write('Starting spool daemon...');
            
            if(!$daemon->run()) {
                return;
            }

            $process = halo\process\Base::getCurrent();
            $justStarted = true;
        }

        if(!$justStarted && $this->_statusData) {
            if(time() - $this->_statusData['statusTime'] > self::THRESHOLD) {
                // Has it got stuck?
                $this->io->writeLine();
                $this->io->write('Status is stale, restarting...');
                
                $process->kill();
                $process = halo\daemon\Base::launch('TaskSpool');
                $justStarted = true;
            }
        }

        $this->io->writeLine(' running with PID: '.$process->getProcessId());
    }
}
