<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event;

use df;
use df\core;
use df\halo;
use df\link;
use df\mesh;

class Select extends Base {

    const SIGNAL = 0;
    const SOCKET = 1;
    const STREAM = 2;
    const TIMER = 3;

    const READ = IIoState::READ;
    const WRITE = IIoState::WRITE;

    const RESOURCE = 0;
    const HANDLER = 1;

    protected $_breakLoop = false;
    protected $_generateMaps = true;

    protected $_socketMap = [];
    protected $_streamMap = [];
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
        $this->_generateMaps();

        $this->_startSignalHandlers();

        while(!$this->_breakLoop) {
            if($this->_generateMaps) {
                $this->_generateMaps();
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

                    $dTime = $times[$id] ?? $baseTime;
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
            if(!empty($this->_socketMap)) {
                $hasHandler = true;
                $read = $this->_socketMap[self::RESOURCE][self::READ];
                $write = $this->_socketMap[self::RESOURCE][self::WRITE];
                $e = null;

                try {
                    $res = socket_select($read, $write, $e, 0, 10000);
                } catch(\Exception $e) {
                    $res = false;
                }

                if($res === false) {
                    // TODO: deal with error
                } else if($res > 0) {
                    foreach($read as $resource) {
                        foreach($this->_socketMap[self::HANDLER][self::READ][(int)$resource] as $id => $binding) {
                            $binding->trigger($resource);
                        }
                    }

                    foreach($write as $resource) {
                        foreach($this->_socketMap[self::HANDLER][self::WRITE][(int)$resource] as $id => $binding) {
                            $binding->trigger($resource);
                        }
                    }
                }

                // TODO: add timeout handler
            }

            // Streams
            if(!empty($this->_streamMap)) {
                $hasHandler = true;
                $read = $this->_streamMap[self::RESOURCE][self::READ];
                $write = $this->_streamMap[self::RESOURCE][self::WRITE];
                $e = null;

                try {
                    $res = stream_select($read, $write, $e, 0, 10000);
                } catch(\Exception $e) {
                    $res = false;
                }

                if($res === false) {
                    // TODO: deal with error
                } else if($res > 0) {
                    foreach($read as $resource) {
                        foreach($this->_streamMap[self::HANDLER][self::READ][(int)$resource] as $id => $binding) {
                            $binding->trigger($resource);
                        }
                    }

                    foreach($write as $resource) {
                        foreach($this->_streamMap[self::HANDLER][self::WRITE][(int)$resource] as $id => $binding) {
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

                    if(false === $this->_cycleHandler->invoke($this)) {
                        $this->stop();
                    }
                }
            }

            if(!$hasHandler) {
                $this->stop();
            }

            usleep(30000);
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
        $this->_socketMap = $this->_streamMap = [
            self::RESOURCE => [
                self::READ => [],
                self::WRITE => []
            ],
            self::HANDLER => [
                self::READ => [],
                self::WRITE => []
            ]
        ];

        $socketCount = $streamCount = 0;



        // Sockets
        foreach($this->_socketBindings as $id => $binding) {
            $resource = $binding->getIoResource();
            $resourceId = (int)$resource;

            if($binding->isStreamBased) {
                $this->_streamMap[self::RESOURCE][$binding->ioMode][$resourceId] = $resource;
                $this->_streamMap[self::HANDLER][$binding->ioMode][$resourceId][$id] = $binding;
                $streamCount++;
            } else {
                $this->_socketMap[self::RESOURCE][$binding->ioMode][$resourceId] = $resource;
                $this->_socketMap[self::HANDLER][$binding->ioMode][$resourceId][$id] = $binding;
                $socketCount++;
            }
        }


        // Streams
        foreach($this->_streamBindings as $id => $binding) {
            $resource = $binding->getIoResource();
            $resourceId = (int)$resource;

            $this->_streamMap[self::RESOURCE][$binding->ioMode][$resourceId] = $resource;
            $this->_streamMap[self::HANDLER][$binding->ioMode][$resourceId][$id] = $binding;
            $streamCount++;
        }


        // Signals
        $this->_signalMap = [];

        foreach($this->_signalBindings as $id => $binding) {
            foreach($binding->signals as $number => $signal) {
                $this->_signalMap[$number][$id] = $binding;
            }
        }

        // Cleanup
        if(!$socketCount) {
            $this->_socketMap = null;
        }

        if(!$streamCount) {
            $this->_streamMap = null;
        }

        $this->_generateMaps = false;
    }

    public function stop() {
        if($this->_isListening) {
            $this->_breakLoop = true;
        }

        return $this;
    }



    public function freezeBinding(IBinding $binding) {
        $binding->isFrozen = true;
        return $this;
    }

    public function unfreezeBinding(IBinding $binding) {
        $binding->isFrozen = false;
        return $this;
    }



// Sockets
    protected function _registerSocketBinding(ISocketBinding $binding) {
        $this->regenerateMaps();
    }

    protected function _unregisterSocketBinding(ISocketBinding $binding) {
        $this->regenerateMaps();
    }



// Streams
    protected function _registerStreamBinding(IStreamBinding $binding) {
        $this->regenerateMaps();
    }

    protected function _unregisterStreamBinding(IStreamBinding $binding) {
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

    protected function _registerSignalBinding(ISignalBinding $binding) {
        $this->regenerateMaps();
    }

    protected function _unregisterSignalBinding(ISignalBinding $binding) {
        $this->regenerateMaps();
    }


// Timers
    protected function _registerTimerBinding(ITimerBinding $binding) {}
    protected function _unregisterTimerBinding(ITimerBinding $binding) {}
}
