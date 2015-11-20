<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\daemons\_nodes;

use df;
use df\core;
use df\apex;
use df\halo;

trait TDaemonTask {

    protected function _ensurePrivileges() {
        $env = core\Environment::getInstance();

        if(!$env->canUseDaemons()) {
            $this->io->writeLine('Daemons are currently disabled in config');
            $this->forceResponse('');
        }

        $process = halo\process\Base::getCurrent();
        $user = $env->getDaemonUser();

        if($user != $process->getOwnerName() && !$process->isPrivileged()) {
            $this->io->writeLine('Restarting task '.$this->request->getPathString().' as root');
            $request = clone $this->request;
            $request->query->_privileged = true;
            $this->task->launch($request, $this->io, null, 'root');
            $this->forceResponse('');
        }
    }

    protected function _hasRestarted() {
        return isset($this->request['_privileged']);
    }
}