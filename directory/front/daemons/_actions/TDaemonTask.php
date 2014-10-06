<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\daemons\_actions;

use df;
use df\core;
use df\apex;
use df\halo;

trait TDaemonTask {

    protected function _beforeDispatch() {
        $process = halo\process\Base::getCurrent();
        $user = core\Environment::getInstance()->getDaemonUser();

        if($user != $process->getOwnerName() && !$process->isPrivileged()) {
            $this->response->writeLine('Restarting task as root');
            $this->task->launch($this->request, $this->response, null, 'root');
            return '';
        }
    }
}