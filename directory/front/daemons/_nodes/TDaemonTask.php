<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\daemons\_nodes;

use DecodeLabs\Genesis;
use DecodeLabs\Systemic;
use DecodeLabs\Terminus as Cli;
use df\core;

trait TDaemonTask
{
    protected function _ensurePrivileges()
    {
        $env = core\environment\Config::getInstance();

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

            $path = Genesis::$hub->getApplicationPath() . '/entry/';
            $path .= Genesis::$environment->getName() . '.php';

            Systemic::scriptCommand([$path, (string)$request])
                ->setWorkingDirectory(Genesis::$hub->getApplicationPath())
                ->setUser($user)
                ->run();
            exit;
        }
    }

    protected function _hasRestarted()
    {
        return isset($this->request['_privileged']);
    }
}
