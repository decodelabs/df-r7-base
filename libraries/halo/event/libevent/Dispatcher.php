<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\libevent;

use df;
use df\core;
use df\halo;

class Dispatcher extends halo\event\DispatcherBase implements IDispatcher {
    
    protected $_base;
    
    public static function getEventTypeFlags($type, $isPersistent=false) {
        switch($type) {
            case halo\event\READ:
                $flags = EV_READ;
                break;
                
            case halo\event\WRITE:
                $flags = EV_WRITE;
                break;
                
            case halo\event\READ_WRITE:
                $flags = EV_READ | EV_WRITE;
                break;
                
            case halo\event\TIMEOUT:
                $flags = EV_TIMEOUT;
                break;
                
            default:
                throw new halo\event\InvalidArgumentException(
                    'Unknown event type: '.$type
                );
        }
        
        if($isPersistent) {
            $flags |= EV_PERSIST;
        }
        
        return $flags;
    }
    
    public function __construct() {
        $this->_base = event_base_new();
    }
    
    public function getEventBase() {
        return $this->_base;
    }
    
    public function start() {
        echo "Starting event loop\n\n";
        
        $this->_isRunning = true;
        event_base_loop($this->_base);
        $this->_isRunning = false;
        
        echo "\nEnding event loop\n";
        
        return $this;
    }
    
    public function stop() {
        if($this->_isRunning) {
            event_base_loopexit($this->_base);
            $this->_isRunning = false;
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