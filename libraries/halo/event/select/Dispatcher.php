<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\select;

use df;
use df\core;
use df\halo;

class Dispatcher extends halo\event\DispatcherBase implements IDispatcher {
    
    const SIGNAL = 0;
    const SOCKET = 1;
    const STREAM = 2;
    const TIMER = 3;
    const COUNTER = 4;
    
    const READ = 0;
    const WRITE = 1;
    
    const RESOURCE = 0;
    const HANDLER = 1;
    
    protected $_breakLoop = false;
    protected $_generateMaps = true;
    
    public function start() {
        echo  "Starting select event loop\n\n";
        
        $this->_breakLoop = false;
        $this->_isRunning = true;
        
        $maps = array();
        
        while(!$this->_breakLoop) {
            if($this->_generateMaps) {
                $maps = $this->_generateMaps();
            }
            
            
            // Timers
            if(isset($maps[self::TIMER])) {
                
            }
            
            // Signals
            if(isset($maps[self::SIGNAL])) {
                
            }
            
            // Sockets
            if(isset($maps[self::SOCKET])) {
                $read = $maps[self::SOCKET][self::RESOURCE][self::READ];
                $write = $maps[self::SOCKET][self::RESOURCE][self::WRITE];
                $e = null;
                
                $res = socket_select($read, $write, $e, 0, 50000);
                
                if($res === false) {
                    // TODO: deal with error
                } else if($res > 0) {
                    foreach($read as $resource) {
                        $handler = $maps[self::SOCKET][self::HANDLER][(int)$resource];
                        $handler->_handleEvent(halo\event\READ);
                    }
                    
                    foreach($write as $resource) {
                        $handler = $maps[self::SOCKET][self::HANDLER][(int)$resource];
                        $handler->_handleEvent(halo\event\WRITE);
                    }
                }
                
                // TODO add timeout handler
            }
            
            // Streams
            if(isset($maps[self::STREAM])) {
                $read = $maps[self::STREAM][self::RESOURCE][self::READ];
                $write = $maps[self::STREAM][self::RESOURCE][self::WRITE];
                $e = null;
                
                $res = stream_select($read, $write, $e, 0, 50000);
                
                if($res === false) {
                    // TODO: deal with error
                } else if($res > 0) {
                    foreach($read as $resource) {
                        $handler = $maps[self::STREAM][self::HANDLER][(int)$resource];
                        $handler->_handleEvent(halo\event\READ);
                    }
                    
                    foreach($write as $resource) {
                        $handler = $maps[self::STREAM][self::HANDLER][(int)$resource];
                        $handler->_handleEvent(halo\event\WRITE);
                    }
                }
                
                // TODO add timeout handler
            }
        }
        
        $this->_breakLoop = false;
        $this->_isRunning = false;
        
        echo "\nEnding select event loop\n";
        
        return $this;
    }
    
    public function regenerateMaps() {
        $this->_generateMaps = true;
        return $this;
    }
    
    private function _generateMaps() {
        $map = array(
            self::SIGNAL => array(),
            self::SOCKET => array(
                self::RESOURCE => array(
                    self::READ => array(),
                    self::WRITE => array()
                ),
                self::HANDLER => array()
            ),
            self::STREAM => array(
                self::RESOURCE => array(
                    self::READ => array(),
                    self::WRITE => array()
                ),
                self::HANDLER => array()
            ),
            self::TIMER => array(),
            self::COUNTER => array(
                self::SIGNAL => 0,
                self::SOCKET => 0,
                self::STREAM => 0,
                self::TIMER => 0
            )
        );
        
        
        foreach($this->_handlers as $handler) {
            $handler->_exportToMap($map);
        }
        
        foreach($map[self::COUNTER] as $key => $count) {
            if($count == 0) {
                unset($map[$key]);
            }
        }
        
        unset($map[self::COUNTER]);
        $this->_generateMaps = false;
        
        return $map;
    }
    
    public function stop() {
        if($this->_isRunning) {
            $this->_breakLoop = true;
        }
        
        return $this;
    }
    
    public function newSocketHandler(halo\socket\ISocket $socket) {
        return $this->_registerHandler(new SocketHandler($this, $socket));
    }
    
    public function newStreamHandler(core\io\stream\IStream $stream) {
        return $this->_registerHandler(new StreamHandler($this, $stream));
    }
    
    public function newSignalHandler($signal) {
        return $this->_registerHandler(new SignalHandler($this, $signal));
    }
    
    public function newTimerHandler(core\time\IDuration $time) {
        return $this->_registerHandler(new TimerHandler($this, $time));
    }
}
