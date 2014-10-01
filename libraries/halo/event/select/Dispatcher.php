<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\select;

use df;
use df\core;
use df\halo;
use df\link;
use df\mesh;

class Dispatcher extends halo\event\Dispatcher {
    
    const SIGNAL = 0;
    const SOCKET = 1;
    const STREAM = 2;
    const TIMER = 3;
    const COUNTER = 4;
    
    const READ = halo\event\IIoState::READ;
    const WRITE = halo\event\IIoState::WRITE;
    
    const RESOURCE = 0;
    const HANDLER = 1;
    
    protected $_breakLoop = false;
    protected $_generateMaps = true;
    protected $_signalMap = [];

    private $_hasPcntl = false;

    public function __construct() {
        $this->_hasPcntl = extension_loaded('pcntl');
    }

    public function listen() {
        $this->_breakLoop = false;
        $this->_isListening = true;
        
        $baseTime = microtime(true);
        $times = [];
        $lastCycle = $baseTime;
        $this->_generateMaps = false;
        $maps = $this->_generateMaps();

        $this->_startSignalHandlers();

        while(!$this->_breakLoop) {
            if($this->_generateMaps) {
                $maps = $this->_generateMaps();
            }
            
            $hasHandler = false;
            

            // Timers
            if(!empty($this->_timerBindings)) {
                $hasHandler = true;
                $time = microtime(true);

                foreach($this->_timerBindings as $id => $binding) {
                    if($binding->isFrozen) {
                        continue;
                    }

                    $dTime = isset($times[$id]) ? $times[$id] : $baseTime;
                    $diff = $time - $dTime;

                    if($diff > $binding->duration->getSeconds()) {
                        $times[$id] = $time;
                        $binding->trigger(null);
                    }
                }
            }


            
            // Signals
            if(!empty($this->_signalBindings) && $this->_hasPcntl) {
                $hasHandler = true;
                pcntl_signal_dispatch();
            }
            
            // Sockets
            if(isset($maps[self::SOCKET])) {
                $hasHandler = true;
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
                        foreach($maps[self::SOCKET][self::HANDLER][self::READ][(int)$resource] as $id => $binding) {
                            $binding->trigger($resource);
                        }
                    }
                    
                    foreach($write as $resource) {
                        foreach($maps[self::SOCKET][self::HANDLER][self::WRITE][(int)$resource] as $id => $binding) {
                            $binding->trigger($resource);
                        }
                    }
                }
                
                // TODO: add timeout handler
            }
            
            // Streams
            if(isset($maps[self::STREAM])) {
                $hasHandler = true;
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
                        foreach($maps[self::STREAM][self::HANDLER][self::READ][(int)$resource] as $id => $binding) {
                            $binding->trigger($resource);
                        }
                    }
                    
                    foreach($write as $resource) {
                        foreach($maps[self::STREAM][self::HANDLER][self::WRITE][(int)$resource] as $id => $binding) {
                            $binding->trigger($resource);
                        }
                    }
                }
                
                // TODO: add timeout handler
            }


            // Cycle
            if($this->_cycleHandler) {
                $time = microtime(true);

                if($time - $lastCycle > 1) {
                    $lastCycle = $time;

                    if(false === $this->_cycleHandler->invokeArgs([$this])) {
                        $this->stop();
                    }
                }
            }

            if(!$hasHandler) {
                $this->stop();
            }

            usleep(10000);
        }
        
        $this->_breakLoop = false;
        $this->_isListening = false;
        
        $this->_stopSignalHandlers();
        
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
                self::HANDLER => [
                    self::READ => [],
                    self::WRITE => []
                ]
            ],
            self::STREAM => [
                self::RESOURCE => [
                    self::READ => [],
                    self::WRITE => []
                ],
                self::HANDLER => [
                    self::READ => [],
                    self::WRITE => []
                ]
            ],
            self::COUNTER => [
                self::SOCKET => 0,
                self::STREAM => 0
            ]
        ];
        

        // Sockets
        foreach($this->_socketBindings as $id => $binding) {
            $resource = $binding->getIoResource();
            $resourceId = (int)$resource;
            $key = $binding->isStreamBased ? self::STREAM : self::SOCKET;

            $map[$key][self::RESOURCE][$binding->ioMode][$resourceId] = $resource;
            $map[$key][self::HANDLER][$binding->ioMode][$resourceId][$id] = $binding;
            $map[self::COUNTER][$key]++;
        }
        

        // Streams
        foreach($this->_streamBindings as $id => $binding) {
            $resource = $binding->getIoResource();
            $resourceId = (int)$resource;

            $map[self::STREAM][self::RESOURCE][$binding->ioMode][$resourceId] = $resource;
            $map[self::STREAM][self::HANDLER][$binding->ioMode][$resourceId][$id] = $binding;
            $map[self::COUNTER][self::STREAM]++;
        }


        // Signals
        $this->_signalMap = [];

        foreach($this->_signalBindings as $id => $binding) {
            foreach($binding->signals as $number => $signal) {
                $this->_signalMap[$number][$id] = $binding;
            }
        }

        // Cleanup
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
        if($this->_isListening) {
            $this->_breakLoop = true;
        }
        
        return $this;
    }
    


    public function freezeBinding(halo\event\IBinding $binding) {
        $binding->isFrozen = true;
        return $this;
    }
    
    public function unfreezeBinding(halo\event\IBinding $binding) {
        $binding->isFrozen = false;
        return $this;
    }



// Sockets
    protected function _registerSocketBinding(halo\event\ISocketBinding $binding) {
        $this->regenerateMaps();
    }

    protected function _unregisterSocketBinding(halo\event\ISocketBinding $binding) {
        $this->regenerateMaps();
    }

    

// Streams
    protected function _registerStreamBinding(halo\event\IStreamBinding $binding) {
        $this->regenerateMaps();
    }

    protected function _unregisterStreamBinding(halo\event\IStreamBinding $binding) {
        $this->regenerateMaps();
    }
    

// Signals
    protected function _startSignalHandlers() {
        if($this->_hasPcntl) {
            foreach($this->_signalMap as $number => $set) {
                pcntl_signal($number, function($number) use($set) {
                    foreach($set as $id => $binding) {
                        $binding->trigger($number);
                    }
                });
            }
        }
    }

    protected function _stopSignalHandlers() {
        if($this->_hasPcntl) {
            foreach($this->_signalMap as $number => $set) {
                pcntl_signal($number, \SIG_IGN);
            }
        }
    }

    protected function _registerSignalBinding(halo\event\ISignalBinding $binding) {
        $this->regenerateMaps();
    }

    protected function _unregisterSignalBinding(halo\event\ISignalBinding $binding) {
        $this->regenerateMaps();
    }


// Timers
    protected function _registerTimerBinding(halo\event\ITimerBinding $binding) {}
    protected function _unregisterTimerBinding(halo\event\ITimerBinding $binding) {}
}
