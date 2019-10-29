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

use DecodeLabs\Terminus\Cli;
use DecodeLabs\Glitch;
use DecodeLabs\Atlas;
use DecodeLabs\Systemic;
use DecodeLabs\Systemic\Process\Managed as ManagedProcess;

class Daemon extends Base
{
    const THRESHOLD = 600;

    protected $_statusData;

    // Execute
    public function dispatch(): void
    {
        if (php_sapi_name() != 'cli') {
            throw Glitch::EDomain(
                'Daemon processes must only be started from the CLI SAPI'
            );
        }

        $env = core\environment\Config::getInstance();

        if (!$env->canUseDaemons() || !extension_loaded('pcntl')) {
            Cli::error('Daemons are not enabled in config');
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
            Cli::error($e->getMessage());
            return;
        }

        $context = new core\SharedContext();
        $settings = $context->data->daemon->settings->select()
            ->where('name', '=', $daemon->getName())
            ->toRow();

        if ($settings) {
            if (!$settings['isEnabled']) {
                Cli::error('Daemon '.$daemon->getName().' is not currently enabled');
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
            Cli::error('You are trying to control this daemon as a user with conflicting permissions - either run it as '.$user.' or with sudo!');
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
                Cli::info('Pausing daemon '.$name);
                $process->sendSignal('SIGTSTP');
                return;

            case 'resume':
                Cli::info('Resuming daemon '.$name);
                $process->sendSignal('SIGCONT');
                return;

            case 'status':
                $this->status($daemon, $process);
                return;

            case 'nudge':
                $this->nudge($daemon, $process);
                return;

            default:
                Cli::error('Unknown commend '.$command);
                Cli::error('Use: start, stop, pause, resume, status, nudge');
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
            Cli::info('Daemon '.$name.' is already running');
            return;
        }

        Cli::write('Starting ');
        Cli::{'brightMagenta'}($name);

        if ($daemon->isTesting()) {
            Cli::newLine();
            $daemon->run();
            return;
        } else {
            Cli::{'yellow'}(': ');
        }

        $entryPath = df\Launchpad::$app->path.'/entry/'.df\Launchpad::$app->envId.'.php';

        $res = Systemic::$process->newScriptLauncher($entryPath, [
                'daemon', $name, '__spawn'
            ])
            ->setIoBroker(Cli::getSession()->getBroker())
            ->setDecoratable(false)
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
            Cli::success('PID: '.$process->getProcessId());
        } else {
            Cli::warning('PID could not be found!');
        }

        return;
    }

    public function stop(halo\daemon\IDaemon $daemon, ManagedProcess $process=null)
    {
        $name = $daemon->getName();

        if (!$process) {
            Cli::info('Daemon '.$name.' is not running');
            return;
        }

        Cli::write('Stopping ');
        Cli::{'brightMagenta'}($name.': ');
        $process->sendSignal('SIGTERM');
        $count = 0;

        while ($process->isAlive()) {
            if ($count++ > 10) {
                Cli::inlineError('TERM failed ');
                Cli::{'yellow'}('Trying KILL: ');
                $process->sendSignal('SIGKILL');
                sleep(5);
                break;
            }

            sleep(1);
        }

        if ($process->isAlive()) {
            Cli::error('still running - fix manually!');
        } else {
            Cli::success('done');
        }
    }

    public function status(halo\daemon\IDaemon $daemon, ManagedProcess $process=null)
    {
        $name = $daemon->getName();

        if (!$process) {
            Cli::info('Daemon '.$name.' is not currently running');
            return;
        }


        if (!$this->_statusData) {
            Cli::info('Daemon '.$name.' is currently running with PID '.$process->getProcessId());
            return;
        }

        if (isset($this->_statusData['state'])) {
            $state = $this->_statusData['state'];
            unset($this->_statusData['state']);
        } else {
            $state = 'unknown';
        }

        Cli::info('Daemon '.$name.' is currently '.$state);

        foreach ($this->_statusData as $key => $value) {
            if (substr($key, -4) == 'Time') {
                $value = (new core\time\Date($value))->localeFormat();
            }

            Cli::info(flex\Text::formatName($key).': '.$value);
        }
    }

    public function nudge(halo\daemon\IDaemon $daemon, ManagedProcess $process=null)
    {
        $name = $daemon->getName();
        Cli::{'yellow'}('Checking daemon '.$name.': ');

        if (!$process) {
            Cli::warning('not running');
            return $this->start($daemon, $process);
        }

        if (isset($this->_statusData['statusTime'])) {
            if (time() - $this->_statusData['statusTime'] > self::THRESHOLD) {
                // Has it got stuck?
                Cli::alert('may have got stuck');
                Cli::{'yellow'}('Status is stale, restarting: ');

                $process->kill();
                $process = halo\daemon\Base::launch('TaskSpool');
            }
        }

        Cli::success('running with PID: '.$process->getProcessId());
    }
}
