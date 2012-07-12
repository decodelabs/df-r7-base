<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\application;

use df;
use df\core;
use df\ctrl;

class Util extends Base {
    
    const RUN_MODE = 'Util';
    
    protected $_ctrlManager;
    
// Execute
    public function dispatch() {
        $this->_beginDispatch();
        
        $this->_ctrlManager = new ctrl\Manager($this);
        $command = core\cli\Command::fromArgv();
        
        if(!$arg = $command[2]) {
            throw new core\InvalidArgumentException(
                'No util command has been specified'
            );
        }
        
        // TODO: parse command
        
        $value = $arg->getValue();
        $arg = core\string\Manipulator::formatId($value);
        $method = '_run'.$arg;
        
        if(!method_exists($this, $method)) {
            throw new core\InvalidArgumentException(
                'Util app does not support the '.$value.' command'
            );
        }
        
        return $this->{$method}();
    }
    
    public function launchPayload($payload) {
        echo $payload;
    }
    
    
    
// Commands
    private function _runHelp() {
        return 'Actions:'."\n".
               '  build-app'."\n".
               '  init-gitignore'."\n\n";
    }


    private function _runBuildApp() {
        $this->_ctrlManager->buildApp();
        
        return 'Build complete'."\n\n";
    }

    private function _runInitGitignore() {
        $this->_ctrlManager->initGitIgnore();

        return '.gitignore file successfully created'."\n\n";
    }
}
