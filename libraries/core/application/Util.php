<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;

class Util extends Base {
    
    const RUN_MODE = 'Util';
    
// Execute
    public function dispatch() {
        $this->_beginDispatch();
        
        $command = core\cli\Command::fromArgv();
        
        if(!$arg = $command[2]) {
            throw new core\InvalidArgumentException(
                'No util command has been specified'
            );
        }
        
        // TODO: parse command
        
        $method = '_run'.ucfirst($arg->getValue());
        
        if(!method_exists($this, $method)) {
            throw new core\InvalidArgumentException(
                'Util app does not support the '.$arg->getValue().' command'
            );
        }
        
        return $this->{$method}();
    }
    
    public function launchPayload($payload) {
        echo $payload;
    }
    
    
    
// Commands
    private function _runBuild() {
        $builder = new df\core\package\Builder(df\Launchpad::$loader);
        $builder->buildTestingInstallation();
        
        return 'Build complete';
    }

}
