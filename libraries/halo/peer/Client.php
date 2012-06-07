<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\peer;

use df;
use df\core;
use df\halo;

abstract class Client implements IClient {
    
    use TPeer;
    
    const PEER_FIRST = 1;
    const CLIENT_FIRST = 2;
    const PEER_STREAM = 3;
    const CLIENT_STREAM = 4;
    const DUPLEX_STREAM = 5;
    
    const PROTOCOL_DISPOSITION = self::CLIENT_FIRST;
    
    public function run() {
        if($this->_isStarted) {
            return $this;
        }
        
        $this->_setup();
        $this->_isStarted = true;
        
        $this->getDispatcher()->start();
    }
    
    protected function _setup() {
        $this->_createSockets();
        
        if(empty($this->_sessions)) {
            throw new RuntimeException(
                'No client sessions have been opened'
            );
        }
        
        $dispatcher = $this->getDispatcher();
        
        foreach($this->_sessions as $session) {
            $socket = $session->getSocket();
            $socket->connect();
            $eventHandler = $dispatcher->newSocketHandler($socket);
            
            switch($this->getProtocolDisposition()) {
                case self::PEER_FIRST:
                    $eventHandler->bind($this, 'dataAvailable', true, [$session]);
                    $eventHandler->freeze($eventHandler->bindWrite($this, 'connectionWaiting', true, [$session]));
                    break;
                    
                case self::CLIENT_FIRST:
                    $eventHandler->bindWrite($this, 'connectionWaiting', true, [$session]);
                    $eventHandler->freeze($eventHandler->bind($this, 'dataAvailable', true, [$session]));
                    break;
                    
                case self::PEER_STREAM:
                    core\stub('Unable to handle peer streams');
                    break;
                    
                case self::CLIENT_STREAM:
                    core\stub('Unable to handle client streams');
                    break;
                    
                case self::DUPLEX_STREAM:
                    core\stub('Unable to handle duplex streams');
                    break;
                    
                default:
                    core\stub('Unknown protocol disposition');
            }
        }
    }
    
    abstract protected function _createSockets();
}