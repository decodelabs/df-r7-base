<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\app\runner;

use df;
use df\core;
use df\halo;
use df\flex;

use DecodeLabs\Glitch;
use DecodeLabs\Atlas;
use DecodeLabs\Systemic;
use DecodeLabs\Systemic\Process\Managed as ManagedProcess;

class Daemon extends Base
{
    const THRESHOLD = 600;

    public $io;
    protected $_statusData;

    // Execute
    public function dispatch(): void
    {
        if (php_sapi_name() != 'cli') {
            throw Glitch::EDomain(
                'Daemon processes must only be started from the CLI SAPI'
            );
        }

        $this->io = new core\io\Std();
        $env = core\environment\Config::getInstance();

        if (!$env->canUseDaemons() || !extension_loaded('pcntl')) {
            $this->io->writeErrorLine('Daemons are not enabled in config');
            return;
        }

        $args = core\cli\Command::fromArgv();

        if (!$arg = $args[2]) {
            throw Glitch::EInvalidArgument(
                'No daemon path has been specified'
            );
        }

        try {
            $daemon = halo\daemon\Base::factory($arg->toString());
        } catch (\Throwable $e) {
            $this->io->writeErrorLine($e->getMessage());
            return;
        }

        $context = new core\SharedContext();
        $settings = $context->data->daemon->settings->select()
            ->where('name', '=', $daemon->getName())
            ->toRow();

        if ($settings) {
            if (!$settings['isEnabled']) {
                $this->io->writeErrorLine('Daemon '.$daemon->getName().' is not currently enabled');
                return;
            }

            if (isset($settings['user'])) {
                $daemon->setUser($settings['user']);
            }

            if (isset($settings['group'])) {
                $daemon->setGroup($settings['group']);
            }
        }

        $currentProcess = Systemic::$process->getCurrent();
        $user = $daemon->getUser();

        if (!$currentProcess->isPrivileged() && $user != $currentProcess->getOwnerName()) {
            $this->io->writeErrorLine('You are trying to control this daemon as a user with conflicting permissions - either run it as '.$user.' or with sudo!');
            return;
        }


        $remote = halo\daemon\Remote::factory($daemon);
        $process = $remote->getProcess();
        $name = $daemon->getName();
        $command = (string)$args[3];

        switch ($command) {
            case '__spawn':
                $this->spawn($daemon, $process);
                return;

            case '':
            case 'start':
                $this->start($daemon, $process);
                return;

            case 'stop':
                $this->stop($daemon, $process);
                return;

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
                $this->status($daemon, $process);
                return;

            case 'nudge':
                $this->nudge($daemon, $process);
                return;

            default:
                $this->io->writeErrorLine('Unknown commend '.$command);
                $this->io->writeErrorLine('Use: start, stop, pause, resume, status, nudge');
                return;
        }
    }

    public function spawn(halo\daemon\IDaemon $daemon, ManagedProcess $process=null)
    {
        if ($process) {
            return;
        }

        $daemon->run();
        return;
    }

    public function start(halo\daemon\IDaemon $daemon, ManagedProcess $process=null)
    {
        $name = $daemon->getName();

        if ($process) {
            $this->io->writeErrorLine('Daemon '.$name.' is already running');
            return;
        }

        $this->io->write('Starting daemon '.$name);

        if ($daemon->isTesting()) {
            $this->io->writeLine();

            $daemon->run();
            return;
        } else {
            $this->io->write('...');
        }

        $entryPath = df\Launchpad::$app->path.'/entry/'.df\Launchpad::$app->envId.'.php';

        $res = Systemic::$process->newScriptLauncher($entryPath, [
                'daemon', $name, '__spawn'
            ])
            ->then([new core\io\Multiplexer([$this->io]), 'exportToAtlasLauncher'])
            ->setDecoratable(false)
            ->setIoBroker(Atlas::newCliBroker())
            ->launch();

        $remote = halo\daemon\Remote::factory($daemon);
        $count = 0;

        while (!$process = $remote->getProcess()) {
            if (++$count > 10) {
                break;
            }

            usleep(500000);
            $remote->refresh();
        }

        if ($process) {
            $this->io->writeLine(' running with PID: '.$process->getProcessId());
        } else {
            $this->io->writeLine(' done, but PID could not be found!');
        }

        return;
    }

    public function stop(halo\daemon\IDaemon $daemon, ManagedProcess $process=null)
    {
        $name = $daemon->getName();

        if (!$process) {
            $this->io->writeLine('Daemon '.$name.' is not running');
            return;
        }

        $this->io->write('Stopping daemon '.$name.'...');
        $process->sendSignal('SIGTERM');
        $count = 0;

        while ($process->isAlive()) {
            if ($count++ > 10) {
                $this->io->writeLine(' TERM failed, trying KILL...');
                $process->sendSignal('SIGKILL');
                sleep(5);
                break;
            }

            sleep(1);
        }

        if ($process->isAlive()) {
            $this->io->writeLine(' still running, not sure what to do now!');
        } else {
            $this->io->writeLine(' done');
        }
    }

    public function status(halo\daemon\IDaemon $daemon, ManagedProcess $process=null)
    {
        $name = $daemon->getName();

        if (!$process) {
            $this->io->writeLine('Daemon '.$name.' is not currently running');
            return;
        }


        if (!$this->_statusData) {
            $this->io->writeLine('Daemon '.$name.' is currently running with PID '.$process->getProcessId());
            return;
        }

        if (isset($this->_statusData['state'])) {
            $state = $this->_statusData['state'];
            unset($this->_statusData['state']);
        } else {
            $state = 'unknown';
        }

        $this->io->writeLine('Daemon '.$name.' is currently '.$state);

        foreach ($this->_statusData as $key => $value) {
            if (substr($key, -4) == 'Time') {
                $value = (new core\time\Date($value))->localeFormat();
            }

            $this->io->writeLine(flex\Text::formatName($key).': '.$value);
        }
    }

    public function nudge(halo\daemon\IDaemon $daemon, ManagedProcess $process=null)
    {
        $name = $daemon->getName();
        $this->io->write('Checking daemon '.$name.'...');

        if (!$process) {
            $this->io->writeLine(' not running');
            return $this->start($daemon, $process);
        }

        if (isset($this->_statusData['statusTime'])) {
            if (time() - $this->_statusData['statusTime'] > self::THRESHOLD) {
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
