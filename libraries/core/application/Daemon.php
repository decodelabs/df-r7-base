<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;
use df\halo;

class Daemon extends Base {
    
    const RUN_MODE = 'Daemon';
    
    
// Execute
    public function dispatch() {
        $this->_beginDispatch();

        if(php_sapi_name() != 'cli') {
            throw new \Exception(
                'Daemon processes must only be started from the CLI SAPI'
            );
        }

        $command = core\cli\Command::fromArgv();

        if(!$arg = $command[2]) {
            throw new core\InvalidArgumentException(
                'No daemon path has been specified'
            );
        }

        return halo\daemon\Base::factory($arg->toString());
    }
    
    public function launchPayload($payload) {
        if(!$payload instanceof halo\daemon\IDaemon) {
            throw new core\InvalidArgumentException(
                'Payload is not a daemon'
            );
        }

        $payload->run();
    }
}
