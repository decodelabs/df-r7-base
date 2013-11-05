<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\process\launcher;

use df;
use df\core;
use df\halo;

class Unix extends Base {

    /*
    public function isPrivileged() {
        core\stub();
    }
    */
    
    public function launch() {
        $command = $this->_prepareCommand();
        
        $descriptors = [
            0 => ['pipe', 'r'], 
            1 => ['pipe', 'w'], 
            2 => ['pipe', 'w']
        ];
        
        $workingDirectory = $this->_workingDirectory !== null ? 
            realpath($this->_workingDirectory) : null;
            
        $result = new halo\process\Result();
        $processHandle = proc_open($command, $descriptors, $pipes, $workingDirectory);
        
        if(!is_resource($processHandle)) {
            return $result->registerFailure();
        } 
        
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);

        $result->setOutput($output);
        $result->setError($error);

        if($this->_multiplexer) {
            if(!empty($output)) {
                $this->_multiplexer->write($output);
            }

            if(!empty($error)) {
                $this->_multiplexer->writeError($error);
            }
        }
        
        foreach($pipes as $pipe) {
            fclose($pipe);
        }
        
        proc_close($processHandle);
        $result->registerCompletion();

        return $result;
    }
    
    public function launchBackground() {
        core\stub($this);
    } 
    
    public function launchManaged() {
        if(!extension_loaded('posix')) {
            throw new halo\process\RuntimeException(
                'Managed processes require the currently unavailable posix extension'
            );
        }
        
        if(!extension_loaded('pcntl')) {
            throw new halo\process\RuntimeException(
                'Managed processes require the currently unavailable pcntl extension'
            );
        }
        
        core\stub($this);
    }
    
    protected function _prepareCommand() {
        $command = '';
        
        if($this->_path) {
            $command .= rtrim($this->_path, '/\\').DIRECTORY_SEPARATOR;
        }
        
        $command .= $this->_processName;
        
        if($this->_args) {
            $command .= ' '.$this->_args;
        }

        if($this->_user) {
            $command = 'sudo -u '.$this->_user.' '.$command;
        }
        
        return $command;
    }
}