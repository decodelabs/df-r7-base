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

    protected $_readChunkSize = 64;

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

        $outputBuffer = $errorBuffer = $input = false;

        $outputStream = new core\io\channel\Stream($pipes[1]);
        $outputStream->setBlocking(false);

        $errorStream = new core\io\channel\Stream($pipes[2]);
        $errorStream->setBlocking(false);

        if($this->_multiplexer) {
            $this->_multiplexer->setReadBlocking(false);
        }

        while(true) {
            $status = proc_get_status($processHandle);

            $outputBuffer = $outputStream->readChunk($this->_readChunkSize);
            $errorBuffer = $errorStream->readChunk($this->_readChunkSize);

            if($this->_multiplexer) {
                $input = $this->_multiplexer->readChunk($this->_readChunkSize);
            }

            if($outputBuffer !== false) {
                $result->appendOutput($outputBuffer);

                if($this->_multiplexer) {
                    $this->_multiplexer->write($outputBuffer);
                }
            }

            if($errorBuffer !== false) {
                $result->appendError($errorBuffer);

                if($this->_multiplexer) {
                    $this->_multiplexer->writeError($errorBuffer);
                }
            }

            if($input !== false) {
                fwrite($pipes[0], $input);
            }

            if(!$status['running'] && $outputBuffer === false && $errorBuffer === false && $input === false) {
                break;
            }

            usleep(10000);
        }

        foreach($pipes as $pipe) {
            fclose($pipe);
        }
        
        proc_close($processHandle);
        $result->registerCompletion();

        if($this->_multiplexer) {
            $this->_multiplexer->setReadBlocking(true);
        }

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
        
        if(!empty($this->_args)) {
            $temp = [];

            foreach($this->_args as $arg) {
                $arg = (string)$arg;

                if($arg{0} != '-') {
                    $arg = escapeshellarg($arg);
                }

                $temp[] = $arg;
            }

            $command .= ' '.implode(' ', $temp);
        }

        if($this->_user) {
            $command = 'sudo -u '.$this->_user.' '.$command;
        }
        
        return $command;
    }
}