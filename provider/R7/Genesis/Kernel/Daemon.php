<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis\Kernel;

use DecodeLabs\Coercion;
use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis\Kernel;
use DecodeLabs\R7\Genesis\KernelTrait;
use DecodeLabs\Systemic;
use DecodeLabs\Systemic\Process;

use DecodeLabs\Terminus as Cli;
use df\core\environment\Config as EnvConfig;
use df\core\SharedContext;
use df\core\time\Date;
use df\halo\daemon\Base as DaemonBase;
use df\halo\daemon\IDaemon;
use df\halo\daemon\Remote;

use Throwable;

class Daemon implements Kernel
{
    use KernelTrait;

    protected const THRESHOLD = 600;



    /**
     * Initialize platform systems
     */
    public function initialize(): void
    {
        Cli::$command
            ->addArgument('initiator', 'Daemon initiator')
            ->addArgument('daemon', 'Daemon name')
            ->addArgument('?command', 'Command to call');
    }

    /**
     * Get run mode
     */
    public function getMode(): string
    {
        return 'Daemon';
    }

    /**
     * Run app
     */
    public function run(): void
    {
        $this->checkEnvironment();

        // Check daemons enabled
        $env = EnvConfig::getInstance();

        if (!$env->canUseDaemons()) {
            Cli::error('Daemons are not enabled in config');
            return;
        }


        // Load daemon
        $args = Cli::getCommand();

        try {
            $daemon = DaemonBase::factory($args['daemon']);
        } catch (Throwable $e) {
            Cli::error($e->getMessage());
            return;
        }


        // Fetch settings
        $context = new SharedContext();
        $settings = $context->data->daemon->settings->select()
            ->where('name', '=', $daemon->getName())
            ->toRow();

        if ($settings) {
            if (!$settings['isEnabled']) {
                Cli::error('Daemon ' . $daemon->getName() . ' is not currently enabled');
                return;
            }

            if (isset($settings['user'])) {
                $daemon->setUser($settings['user']);
            }

            if (isset($settings['group'])) {
                $daemon->setGroup($settings['group']);
            }
        }


        // Check privileges
        $currentProcess = Systemic::getCurrentProcess();
        $user = $daemon->getUser();

        /*
        if (
            !$currentProcess->isPrivileged() &&
            $user != $currentProcess->getOwnerName()
        ) {
            Cli::error('You are trying to control this daemon as a user with conflicting permissions - either run it as ' . $user . ' or with sudo!');
            return;
        }
        */


        // Run command
        $remote = Remote::factory($daemon);
        $process = $remote->getProcess();
        $name = $daemon->getName();
        $command = Coercion::toString($args['command'] ?? 'restart');

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
                Cli::info('Pausing daemon ' . $name);
                $process->sendSignal('SIGTSTP');
                return;

            case 'resume':
                Cli::info('Resuming daemon ' . $name);
                $process->sendSignal('SIGCONT');
                return;

            case 'status':
                $this->status($daemon, $process);
                return;

            case 'nudge':
                $this->nudge($daemon, $process);
                return;

            default:
                Cli::error('Unknown command ' . $command);
                Cli::error('Use: start, stop, pause, resume, status, nudge');
                return;
        }
    }


    /**
     * Ensure Daemons can run in environment
     */
    protected function checkEnvironment(): void
    {
        if (php_sapi_name() != 'cli') {
            throw Exceptional::Domain(
                'Daemon processes must only be started from the CLI SAPI'
            );
        }

        if (!extension_loaded('pcntl')) {
            throw Exceptional::ComponentUnavailable('Pcntl is not available');
        }
    }




    /**
     * Spawn the running Daemon process
     */
    protected function spawn(
        IDaemon $daemon,
        ?Process $process = null
    ): void {
        if ($process) {
            return;
        }

        $daemon->run();
        return;
    }


    /**
     * Trigger launch of process
     */
    protected function start(
        IDaemon $daemon,
        ?Process $process = null
    ): void {
        $name = $daemon->getName();

        if ($process) {
            Cli::info('Daemon ' . $name . ' is already running');
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

        $entryPath =
            $this->context->hub->getApplicationPath() . '/entry/' .
            $this->context->environment->getName() . '.php';

        Systemic::runScript([$entryPath, 'daemon', $name, '__spawn']);

        $remote = Remote::factory($daemon);
        $count = 0;

        while (!$process = $remote->getProcess()) {
            if (++$count > 10) {
                break;
            }

            usleep(500000);
            $remote->refresh();
        }

        if ($process) {
            Cli::success('PID: ' . $process->getProcessId());
        } else {
            Cli::warning('PID could not be found!');
        }

        return;
    }



    /**
     * Tell process to stop
     */
    protected function stop(
        IDaemon $daemon,
        ?Process $process = null
    ): void {
        $name = $daemon->getName();

        if (!$process) {
            Cli::info('Daemon ' . $name . ' is not running');
            return;
        }

        Cli::write('Stopping ');
        Cli::{'brightMagenta'}($name . ': ');

        $process->sendSignal('SIGINT');
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


    /**
     * Lookup process status
     */
    protected function status(
        IDaemon $daemon,
        ?Process $process = null
    ): void {
        $name = $daemon->getName();

        if (!$process) {
            Cli::info('Daemon ' . $name . ' is not currently running');
            return;
        }


        $remote = Remote::factory($daemon);
        $status = $remote->getStatusData();

        Cli::info('Daemon ' . $name . ' is currently running');

        foreach ($status as $key => $value) {
            if (substr($key, -4) == 'Time') {
                $value = (new Date($value))->localeFormat();
            }

            Cli::write(' - ');
            Cli::{'brightMagenta'}(Dictum::name($key));
            Cli::write(': ');
            Cli::{'.brightYellow'}((string)$value);
        }
    }

    protected function nudge(
        IDaemon $daemon,
        ?Process $process = null
    ): void {
        $name = $daemon->getName();
        Cli::{'yellow'}('Checking daemon ' . $name . ': ');

        if (!$process) {
            Cli::warning('not running');
            $this->start($daemon, $process);
            return;
        }

        $remote = Remote::factory($daemon);
        $status = $remote->getStatusData();

        if (
            isset($status['statusTime']) &&
            time() - $status['statusTime'] > self::THRESHOLD
        ) {
            // Has it got stuck?
            Cli::warning('may have got stuck');
            Cli::{'.brightRed'}('Status is stale, restarting: ');

            $this->stop($daemon, $process);
            $this->start($daemon);
            return;
        }

        Cli::success('running with PID: ' . $process->getProcessId());
    }
}
