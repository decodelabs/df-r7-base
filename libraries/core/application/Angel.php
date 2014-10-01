<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;
use df\halo;
    
class Angel extends Base {

    const RUN_MODE = 'Angel';


// Execute
    public function dispatch() {
        $this->_beginDispatch();

        if(php_sapi_name() != 'cli') {
            throw new \Exception(
                'Angel processes must only be started from the CLI SAPI'
            );
        }

        return halo\daemon\Base::factory('Angel');
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