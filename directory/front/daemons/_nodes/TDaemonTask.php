<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\daemons\_nodes;

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

        if ($user != $process->getOwnerName() && !$process->isPrivileged() && !isset($this->request['_privileged'])) {
            Cli::notice('Restarting task ' . $this->request->getPathString() . ' as root');
            $request = clone $this->request;
            $request->query->_privileged = true;


            $this->task->launch(
                $request,
                Cli::getSession(),
                'root',
                true
            );

            $this->forceResponse('');
        }
    }

    protected function _hasRestarted()
    {
        return isset($this->request['_privileged']);
    }
}
