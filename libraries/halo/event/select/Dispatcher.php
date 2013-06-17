<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\select;

use df;
use df\core;
use df\halo;

class Dispatcher extends halo\event\Dispatcher {
    
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
        //echo  "Starting select event loop\n\n";
        
        $this->_breakLoop = false;
        $this->_isRunning = true;
        
        $maps = array();
        $baseTime = microtime(true);
        $times = array();
        
        while(!$this->_breakLoop) {
            if($this->_generateMaps) {
                $maps = $this->_generateMaps();
            }
            
            
            // Timers
            if(!empty($this->_timerHandlers)) {
                $time = microtime(true);

                foreach($this->_timerHandlers as $id => $timer) {
                    $dTime = isset($times[$id]) ? $times[$id] : $baseTime;
                    $diff = $time - $dTime;

                    if($diff > $timer->duration->getSeconds()) {
                        $times[$id] = $time;
                        call_user_func_array($timer->callback, [$id]);

                        if(!$timer->isPersistent) {
                            $this->removeTimer($id);
                        }
                    }
                }
            }
            
            // Signals
            if(!empty($this->_signalHandlers) && extension_loaded('pcntl')) {
                pcntl_signal_dispatch();
            }
            
            // Sockets
            if(isset($maps[self::SOCKET])) {
                $read = $maps[self::SOCKET][self::RESOURCE][self::READ];
                $write = $maps[self::SOCKET][self::RESOURCE][self::WRITE];
                $e = null;
                
                try {
                    $res = socket_select($read, $write, $e, 0, 50000);
                } catch(\Exception $e) {
                    $res = false;
                }
                
                if($res === false) {
                    // TODO: deal with error
                } else if($res > 0) {
                    foreach($read as $resource) {
                        $handler = $maps[self::SOCKET][self::HANDLER][(int)$resource];
                        $handler->_handleEvent(halo\event\IIoState::READ);
                    }
                    
                    foreach($write as $resource) {
                        $handler = $maps[self::SOCKET][self::HANDLER][(int)$resource];
                        $handler->_handleEvent(halo\event\IIoState::WRITE);
                    }
                }
                
                // TODO: add timeout handler
            }
            
            // Streams
            if(isset($maps[self::STREAM])) {
                $read = $maps[self::STREAM][self::RESOURCE][self::READ];
                $write = $maps[self::STREAM][self::RESOURCE][self::WRITE];
                $e = null;
                
                try {
                    $res = stream_select($read, $write, $e, 0, 50000);
                } catch(\Exception $e) {
                    $res = false;
                }
                
                if($res === false) {
                    // TODO: deal with error
                } else if($res > 0) {
                    foreach($read as $resource) {
                        $handler = $maps[self::STREAM][self::HANDLER][(int)$resource];
                        $handler->_handleEvent(halo\event\IIoState::READ);
                    }
                    
                    foreach($write as $resource) {
                        $handler = $maps[self::STREAM][self::HANDLER][(int)$resource];
                        $handler->_handleEvent(halo\event\IIoState::WRITE);
                    }
                }
                
                // TODO: add timeout handler
            }

            if($this->_cycleHandler) {
                if(false === call_user_func_array($this->_cycleHandler, [$this])) {
                    $this->stop();
                }
            }

            usleep(10000);
        }
        
        $this->_breakLoop = false;
        $this->_isRunning = false;
        
        //echo "\nEnding select event loop\n";
        
        return $this;
    }
    
    public function regenerateMaps() {
        $this->_generateMaps = true;
        return $this;
    }
    
    private function _generateMaps() {
        $map = [
            self::SOCKET => [
                self::RESOURCE => [
                    self::READ => [],
                    self::WRITE => []
                ],
                self::HANDLER => []
            ],
            self::STREAM => [
                self::RESOURCE => [
                    self::READ => [],
                    self::WRITE => []
                ],
                self::HANDLER => []
            ],
            self::COUNTER => [
                self::SOCKET => 0,
                self::STREAM => 0
            ]
        ];
        
        
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
        return $this->_registerHandler(new Handler_Socket($this, $socket));
    }
    
    public function newStreamHandler(core\io\stream\IStream $stream) {
        return $this->_registerHandler(new Handler_Stream($this, $stream));
    }
    

// Signals
    protected function _registerSignalHandler(halo\process\ISignal $signal, Callable $handler) {
        if(extension_loaded('pcntl')) {
            pcntl_signal($signal->getNumber(), function() use($signal, $handler) { 
                call_user_func_array($handler, [$signal]);
            });
        }
    }

    protected function _unregisterSignalHandler(halo\process\ISignal $signal) {
        if(extension_loaded('pcntl')) {
            pcntl_signal($signal->getNumber(), function() use($signal) {});
        }
    }


// Timers
    protected function _registerTimer(halo\event\Timer $timer) {}
    protected function _unregisterTimer(halo\event\Timer $timer) {}
}
