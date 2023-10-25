<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\daemons\_nodes;

use DecodeLabs\R7\Config\Environment as EnvironmentConfig;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Systemic;
use DecodeLabs\Terminus as Cli;

trait TDaemonTask
{
    protected function _ensurePrivileges()
    {
        $env = EnvironmentConfig::load();

        if (!$env->canUseDaemons()) {
            Cli::warning('Daemons are currently disabled in config');
            $this->forceResponse('');
        }

        $process = Systemic::getCurrentProcess();
        $user = $env->getDaemonUser();

        if (
            defined('STDOUT') &&
            stream_isatty(\STDOUT) &&
            $user != $process->getOwnerName() &&
            !$process->isPrivileged() &&
            !isset($this->request['_privileged'])
        ) {
            Cli::notice('Restarting task ' . $this->request->getPathString() . ' as root');
            $request = clone $this->request;
            $request->query->_privileged = true;

            Legacy::taskCommand($request)
                ->setUser('root')
                ->run();
            exit;
        }
    }

    protected function _hasRestarted()
    {
        return isset($this->request['_privileged']);
    }
}
