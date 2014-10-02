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

abstract class Base implements IDispatcher {
    
    protected $_isListening = false;
    protected $_cycleHandler;
    protected $_socketBindings = [];
    protected $_streamBindings = [];
    protected $_signalBindings = [];
    protected $_timerBindings = [];


    public static function factory() {
        if(extension_loaded('libevent')) {
            return new halo\event\LibEvent();
        }
        
        return new halo\event\Select();
    }


    public function isListening() {
        return $this->_isListening;
    }


    public function freezeAllBindings() {
        $this->freezeAllSockets();
        $this->freezeAllStreams();
        $this->freezeAllSignals();
        $this->freezeAllTimers();

        return $this;
    }

    public function unfreezeAllBindings() {
        $this->unfreezeAllSockets();
        $this->unfreezeAllStreams();
        $this->unfreezeAllSignals();
        $this->unfreezeAllTimers();

        return $this;
    }

    public function removeAllBindings() {
        $this->removeAllSockets();
        $this->removeAllStreams();
        $this->removeAllSignals();
        $this->removeAllTimers();

        return $this;
    }

    public function getAllBindings() {
        return array_merge(
            array_values($this->_socketBindings),
            array_values($this->_streamBindings),
            array_values($this->_signalBindings),
            array_values($this->_timerBindings)
        );
    }

    public function countAllBindings() {
        return count($this->_socketBindings)
             + count($this->_streamBindings)
             + count($this->_signalBindings)
             + count($this->_timerBindings);
    }


// Cycle handler
    public function setCycleHandler($callback=null) {
        if($callback !== null) {
            $callback = mesh\Callback::factory($callback);
        }

        $this->_cycleHandler = $callback;
        return $this;
    }

    public function getCycleHandler() {
        return $this->_cycleHandler;
    }

    

// Socket
    public function bindSocketRead(link\socket\ISocket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addSocketBinding(new halo\event\binding\Socket($this, true, $socket, IIoState::READ, $callback, $timeoutDuration, $timeoutCallback), false);
    }

    public function bindFrozenSocketRead(link\socket\ISocket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addSocketBinding(new halo\event\binding\Socket($this, true, $socket, IIoState::READ, $callback, $timeoutDuration, $timeoutCallback), true);
    }

    public function bindSocketReadOnce(link\socket\ISocket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addSocketBinding(new halo\event\binding\Socket($this, false, $socket, IIoState::READ, $callback, $timeoutDuration, $timeoutCallback), false);
    }

    public function bindFrozenSocketReadOnce(link\socket\ISocket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addSocketBinding(new halo\event\binding\Socket($this, false, $socket, IIoState::READ, $callback, $timeoutDuration, $timeoutCallback), true);
    }

    public function bindSocketWrite(link\socket\ISocket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addSocketBinding(new halo\event\binding\Socket($this, true, $socket, IIoState::WRITE, $callback, $timeoutDuration, $timeoutCallback), false);
    }

    public function bindFrozenSocketWrite(link\socket\ISocket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addSocketBinding(new halo\event\binding\Socket($this, true, $socket, IIoState::WRITE, $callback, $timeoutDuration, $timeoutCallback), true);
    }

    public function bindSocketWriteOnce(link\socket\ISocket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addSocketBinding(new halo\event\binding\Socket($this, false, $socket, IIoState::WRITE, $callback, $timeoutDuration, $timeoutCallback), false);
    }

    public function bindFrozenSocketWriteOnce(link\socket\ISocket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addSocketBinding(new halo\event\binding\Socket($this, false, $socket, IIoState::WRITE, $callback, $timeoutDuration, $timeoutCallback), true);
    }

    protected function _addSocketBinding(ISocketBinding $binding, $frozen) {
        $id = $binding->getId();

        if(isset($this->_socketBindings[$id])) {
            $this->removeSocketBinding($binding);
        }

        $this->_socketBindings[$id] = $binding;

        if($frozen) {
            $binding->isFrozen = true;
        } else {
            $this->_registerSocketBinding($binding);
        }

        return $this;
    }

    abstract protected function _registerSocketBinding(ISocketBinding $binding);
    abstract protected function _unregisterSocketBinding(ISocketBinding $binding);



    public function freezeSocket(link\socket\ISocket $socket) {
        $id = $socket->getId();

        if(isset($this->_socketBindings['r:'.$id])) {
            $this->freezeBinding($this->_socketBindings['r:'.$id]);
        }

        if(isset($this->_socketBindings['w:'.$id])) {
            $this->freezeBinding($this->_socketBindings['w:'.$id]);
        }

        return $this;
    }

    public function freezeSocketRead(link\socket\ISocket $socket) {
        $id = $socket->getId();

        if(isset($this->_socketBindings['r:'.$id])) {
            $this->freezeBinding($this->_socketBindings['r:'.$id]);
        }

        return $this;
    }

    public function freezeSocketWrite(link\socket\ISocket $socket) {
        $id = $socket->getId();

        if(isset($this->_socketBindings['w:'.$id])) {
            $this->freezeBinding($this->_socketBindings['w:'.$id]);
        }

        return $this;
    }

    public function freezeAllSockets() {
        foreach($this->_socketBindings as $id => $binding) {
            $this->freezeBinding($binding);
        }

        return $this;
    }



    public function unfreezeSocket(link\socket\ISocket $socket) {
        $id = $socket->getId();

        if(isset($this->_socketBindings['r:'.$id])) {
            $this->unfreezeBinding($this->_socketBindings['r:'.$id]);
        }

        if(isset($this->_socketBindings['w:'.$id])) {
            $this->unfreezeBinding($this->_socketBindings['w:'.$id]);
        }

        return $this;
    }

    public function unfreezeSocketRead(link\socket\ISocket $socket) {
        $id = $socket->getId();

        if(isset($this->_socketBindings['r:'.$id])) {
            $this->unfreezeBinding($this->_socketBindings['r:'.$id]);
        }

        return $this;
    }

    public function unfreezeSocketWrite(link\socket\ISocket $socket) {
        $id = $socket->getId();

        if(isset($this->_socketBindings['w:'.$id])) {
            $this->unfreezeBinding($this->_socketBindings['w:'.$id]);
        }

        return $this;
    }
    

    public function unfreezeAllSockets() {
        foreach($this->_socketBindings as $id => $binding) {
            $this->unfreezeBinding($binding);
        }

        return $this;
    }



    public function removeSocket(link\socket\ISocket $socket) {
        $id = $socket->getId();

        if(isset($this->_socketBindings['r:'.$id])) {
            $this->removeSocketBinding($this->_socketBindings['r:'.$id]);
        }

        if(isset($this->_socketBindings['w:'.$id])) {
            $this->removeSocketBinding($this->_socketBindings['w:'.$id]);
        }

        return $this;
    }

    public function removeSocketRead(link\socket\ISocket $socket) {
        $id = $socket->getId();

        if(isset($this->_socketBindings['r:'.$id])) {
            $this->removeSocketBinding($this->_socketBindings['r:'.$id]);
        }

        return $this;
    }

    public function removeSocketWrite(link\socket\ISocket $socket) {
        $id = $socket->getId();

        if(isset($this->_socketBindings['w:'.$id])) {
            $this->removeSocketBinding($this->_socketBindings['w:'.$id]);
        }

        return $this;
    }

    public function removeSocketBinding(ISocketBinding $binding) {
        $this->_unregisterSocketBinding($binding);
        unset($this->_socketBindings[$binding->id]);

        return $this;
    }

    public function removeAllSockets() {
        foreach($this->_socketBindings as $id => $binding) {
            $this->_unregisterSocketBinding($binding);
            unset($this->_socketBindings[$id]);
        }

        return $this;
    }

    
    public function countSocketBindings(link\socket\ISocket $socket=null) {
        if(!$socket) {
            return count($this->_socketBindings);
        }

        $count = 0;
        $id = $socket->getId();

        if(isset($this->_socketBindings['r:'.$id])) {
            $count++;
        }

        if(isset($this->_socketBindings['w:'.$id])) {
            $count++;
        }

        return $count;
    }

    public function getSocketBindings(link\socket\ISocket $socket=null) {
        if(!$socket) {
            return $this->_socketBindings;
        }

        $output = [];
        $id = $socket->getId();

        if(isset($this->_socketBindings['r:'.$id])) {
            $output['r:'.$id] = $this->_socketBindings['r:'.$id];
        }

        if(isset($this->_socketBindings['w:'.$id])) {
            $output['w:'.$id] = $this->_socketBindings['w:'.$id];
        }

        return $output;
    }

    public function countSocketReadBindings() {
        $count = 0;

        foreach($this->_socketBindings as $binding) {
            if($binding->ioMode == IIoState::READ) {
                $count++;
            }
        }

        return $count;
    }

    public function getSocketReadBindings() {
        $output = [];

        foreach($this->_socketBindings as $id => $binding) {
            if($binding->ioMode == IIoState::READ) {
                $output[$id] = $binding;
            }
        }

        return $output;
    }

    public function countSocketWriteBindings() {
        $count = 0;

        foreach($this->_socketBindings as $binding) {
            if($binding->ioMode == IIoState::WRITE) {
                $count++;
            }
        }

        return $count;
    }

    public function getSocketWriteBindings() {
        $output = [];

        foreach($this->_socketBindings as $id => $binding) {
            if($binding->ioMode == IIoState::WRITE) {
                $output[$id] = $binding;
            }
        }

        return $output;
    }


// Stream
    public function bindStreamRead(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addStreamBinding(new halo\event\binding\Stream($this, true, $stream, IIoState::READ, $callback, $timeoutDuration, $timeoutCallback), false);
    }

    public function bindFrozenStreamRead(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addStreamBinding(new halo\event\binding\Stream($this, true, $stream, IIoState::READ, $callback, $timeoutDuration, $timeoutCallback), true);
    }

    public function bindStreamReadOnce(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addStreamBinding(new halo\event\binding\Stream($this, false, $stream, IIoState::READ, $callback, $timeoutDuration, $timeoutCallback), false);
    }

    public function bindFrozenStreamReadOnce(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addStreamBinding(new halo\event\binding\Stream($this, false, $stream, IIoState::READ, $callback, $timeoutDuration, $timeoutCallback), true);
    }

    public function bindStreamWrite(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addStreamBinding(new halo\event\binding\Stream($this, true, $stream, IIoState::WRITE, $callback, $timeoutDuration, $timeoutCallback), false);
    }

    public function bindFrozenStreamWrite(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addStreamBinding(new halo\event\binding\Stream($this, true, $stream, IIoState::WRITE, $callback, $timeoutDuration, $timeoutCallback), true);
    }

    public function bindStreamWriteOnce(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addStreamBinding(new halo\event\binding\Stream($this, false, $stream, IIoState::WRITE, $callback, $timeoutDuration, $timeoutCallback), false);
    }

    public function bindFrozenStreamWriteOnce(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        return $this->_addStreamBinding(new halo\event\binding\Stream($this, false, $stream, IIoState::WRITE, $callback, $timeoutDuration, $timeoutCallback), true);
    }

    protected function _addStreamBinding(IStreamBinding $binding, $frozen) {
        $id = $binding->getId();

        if(isset($this->_streamBindings[$id])) {
            $this->removeStream($binding);
        }

        $this->_streamBindings[$id] = $binding;

        if($frozen) {
            $binding->isFrozen = true;
        } else {
            $this->_registerStreamBinding($binding);
        }

        return $this;
    }

    abstract protected function _registerStreamBinding(IStreamBinding $binding);
    abstract protected function _unregisterStreamBinding(IStreamBinding $binding);



    public function freezeStream(core\io\IStreamChannel $stream) {
        $id = $stream->getChannelId();

        if(isset($this->_streamBindings['r:'.$id])) {
            $this->freezeBinding($this->_streamBindings['r:'.$id]);
        }

        if(isset($this->_streamBindings['w:'.$id])) {
            $this->freezeBinding($this->_streamBindings['w:'.$id]);
        }

        return $this;
    }

    public function freezeStreamRead(core\io\IStreamChannel $stream) {
        $id = $stream->getChannelId();

        if(isset($this->_streamBindings['r:'.$id])) {
            $this->freezeBinding($this->_streamBindings['r:'.$id]);
        }

        return $this;
    }

    public function freezeStreamWrite(core\io\IStreamChannel $stream) {
        $id = $stream->getChannelId();

        if(isset($this->_streamBindings['w:'.$id])) {
            $this->freezeBinding($this->_streamBindings['w:'.$id]);
        }

        return $this;
    }

    public function freezeAllStreams() {
        foreach($this->_streamBindings as $id => $binding) {
            $this->freezeBinding($binding);
        }

        return $this;
    }




    public function unfreezeStream(core\io\IStreamChannel $stream) {
        $id = $stream->getChannelId();

        if(isset($this->_streamBindings['r:'.$id])) {
            $this->unfreezeBinding($this->_streamBindings['r:'.$id]);
        }

        if(isset($this->_streamBindings['w:'.$id])) {
            $this->unfreezeBinding($this->_streamBindings['w:'.$id]);
        }

        return $this;
    }

    public function unfreezeStreamRead(core\io\IStreamChannel $stream) {
        $id = $stream->getChannelId();

        if(isset($this->_streamBindings['r:'.$id])) {
            $this->unfreezeBinding($this->_streamBindings['r:'.$id]);
        }

        return $this;
    }

    public function unfreezeStreamWrite(core\io\IStreamChannel $stream) {
        $id = $stream->getChannelId();

        if(isset($this->_streamBindings['w:'.$id])) {
            $this->unfreezeBinding($this->_streamBindings['w:'.$id]);
        }

        return $this;
    }

    public function unfreezeAllStreams() {
        foreach($this->_streamBindings as $id => $binding) {
            $this->unfreezeBinding($binding);
        }

        return $this;
    }



    public function removeStream(core\io\IStreamChannel $stream) {
        $id = $stream->getId();

        if(isset($this->_streamBindings['r:'.$id])) {
            $this->removeStreamBinding($this->_streamBindings['r:'.$id]);
        }

        if(isset($this->_streamBindings['w:'.$id])) {
            $this->removeStreamBinding($this->_streamBindings['w:'.$id]);
        }

        return $this;
    }

    public function removeStreamRead(core\io\IStreamChannel $stream) {
        $id = $stream->getId();

        if(isset($this->_streamBindings['r:'.$id])) {
            $this->removeStreamBinding($this->_streamBindings['r:'.$id]);
        }

        return $this;
    }

    public function removeStreamWrite(core\io\IStreamChannel $stream) {
        $id = $stream->getId();

        if(isset($this->_streamBindings['w:'.$id])) {
            $this->removeStreamBinding($this->_streamBindings['w:'.$id]);
        }

        return $this;
    }

    public function removeStreamBinding(IStreamBinding $binding) {
        $this->_unregisterStreamBinding($binding);
        unset($this->_streamBindings[$binding->id]);

        return $this;
    }

    public function removeAllStreams() {
        foreach($this->_streamBindings as $id => $binding) {
            $this->_unregisterStreamBinding($binding);
            unset($this->_streamBindings[$id]);
        }

        return $this;
    }




    public function countStreamBindings(core\io\IStreamChannel $stream=null) {
        if(!$stream) {
            return count($this->_streamBindings);
        }

        $count = 0;
        $id = $stream->getId();

        if(isset($this->_streamBindings['r:'.$id])) {
            $count++;
        }

        if(isset($this->_streamBindings['w:'.$id])) {
            $count++;
        }

        return $count;
    }

    public function getStreamBindings(core\io\IStreamChannel $stream=null) {
        if(!$stream) {
            return $this->_streamBindings;
        }

        $output = [];
        $id = $stream->getId();

        if(isset($this->_streamBindings['r:'.$id])) {
            $output['r:'.$id] = $this->_streamBindings['r:'.$id];
        }

        if(isset($this->_streamBindings['w:'.$id])) {
            $output['w:'.$id] = $this->_streamBindings['w:'.$id];
        }

        return $output;
    }

    public function countStreamReadBindings() {
        $count = 0;

        foreach($this->_streamBindings as $binding) {
            if($binding->ioMode == IIoState::READ) {
                $count++;
            }
        }

        return $count;
    }

    public function getStreamReadBindings() {
        $output = [];

        foreach($this->_streamBindings as $id => $binding) {
            if($binding->ioMode == IIoState::READ) {
                $output[$id] = $binding;
            }
        }

        return $output;
    }

    public function countStreamWriteBindings() {
        $count = 0;

        foreach($this->_streamBindings as $binding) {
            if($binding->ioMode == IIoState::WRITE) {
                $count++;
            }
        }

        return $count;
    }

    public function getStreamWriteBindings() {
        $output = [];

        foreach($this->_streamBindings as $id => $binding) {
            if($binding->ioMode == IIoState::WRITE) {
                $output[$id] = $binding;
            }
        }

        return $output;
    }

    

// Signals
    public function bindSignal($id, $signals, $callback) {
        return $this->_addSignalBinding(new halo\event\binding\Signal($this, $id, true, $signals, $callback), false);
    }

    public function bindFrozenSignal($id, $signals, $callback) {
        return $this->_addSignalBinding(new halo\event\binding\Signal($this, $id, true, $signals, $callback), true);
    }

    public function bindSignalOnce($id, $signals, $callback) {
        return $this->_addSignalBinding(new halo\event\binding\Signal($this, $id, false, $signals, $callback), false);
    }

    public function bindFrozenSignalOnce($id, $signals, $callback) {
        return $this->_addSignalBinding(new halo\event\binding\Signal($this, $id, false, $signals, $callback), true);
    }

    protected function _addSignalBinding(ISignalBinding $binding, $frozen) {
        $id = $binding->getId();

        if(isset($this->_signalBindings[$id])) {
            $this->removeSignal($binding);
        }

        $this->_signalBindings[$id] = $binding;

        if($frozen) {
            $binding->isFrozen = true;
        } else {
            $this->_registerSignalBinding($binding);
        }

        return $this;
    }

    abstract protected function _registerSignalBinding(ISignalBinding $binding);
    abstract protected function _unregisterSignalBinding(ISignalBinding $binding);

    public function freezeSignal($signal) {
        $number = halo\process\Signal::factory($signal)->getNumber();

        foreach($this->_signalBindings as $id => $binding) {
            if(isset($binding->signals[$number])) {
                $this->freezeBinding($binding);
            }
        }

        return $this;
    }

    public function freezeSignalBinding($binding) {
        if(!$binding instanceof ISignalBinding) {
            $binding = $this->getSignalBinding($binding);
        }
        
        $this->freezeBinding($binding);
        return $this;
    }

    public function freezeAllSignals() {
        foreach($this->_signalBindings as $id => $binding) {
            $this->freezeBinding($binding);
        }

        return $this;
    }


    public function unfreezeSignal($signal) {
        $number = halo\process\Signal::factory($signal)->getNumber();

        foreach($this->_signalBindings as $id => $binding) {
            if(isset($binding->signals[$number])) {
                $this->unfreezeBinding($binding);
            }
        }

        return $this;
    }

    public function unfreezeSignalBinding($binding) {
        if(!$binding instanceof ISignalBinding) {
            $binding = $this->getSignalBinding($binding);
        }
        
        $this->unfreezeBinding($binding);
        return $this;
    }

    public function unfreezeAllSignals() {
        foreach($this->_signalBindings as $id => $binding) {
            $this->unfreezeBinding($binding);
        }

        return $this;
    }


    public function removeSignal($signal) {
        $number = halo\process\Signal::factory($signal)->getNumber();

        foreach($this->_signalBindings as $id => $binding) {
            if(isset($binding->signals[$number])) {
                $this->removeSignalBinding($binding);
            }
        }

        return $this;
    }

    public function removeSignalBinding($binding) {
        if(!$binding instanceof ISignalBinding) {
            $binding = $this->getSignalBinding($binding);
        }
        
        $id = $binding->getId();
        $this->_unregisterSignalBinding($binding);
        unset($this->_signalBindings[$id]);

        return $this;
    }

    public function removeAllSignals() {
        foreach($this->_signalBindings as $id => $binding) {
            $this->_unregisterSignalBinding($binding);
            unset($this->_signalBindings[$id]);
        }

        return $this;
    }

    
    public function getSignalBinding($id) {
        if($id instanceof ISignalBinding) {
            $id = $id->getId();
        }

        if(!isset($this->_signalBindings[$id])) {
            throw new RuntimeException(
                'Signal binding \''.$id.'\' could not be found'
            );
        }

        return $this->_signalBindings[$id];
    }

    public function countSignalBindings() {
        return count($this->_signalBindings);
    }

    public function getSignalBindings() {
        return $this->_signalBindings;
    }


// Timers
    public function bindTimer($id, $duration, $callback) {
        return $this->_addTimerBinding(new halo\event\binding\Timer($this, $id, true, $duration, $callback), false);
    }

    public function bindFrozenTimer($id, $duration, $callback) {
        return $this->_addTimerBinding(new halo\event\binding\Timer($this, $id, true, $duration, $callback), true);
    }

    public function bindTimerOnce($id, $duration, $callback) {
        return $this->_addTimerBinding(new halo\event\binding\Timer($this, $id, false, $duration, $callback), false);
    }

    public function bindFrozenTimerOnce($id, $duration, $callback) {
        return $this->_addTimerBinding(new halo\event\binding\Timer($this, $id, false, $duration, $callback), true);
    }

    protected function _addTimerBinding(ITimerBinding $binding, $frozen) {
        $id = $binding->getId();

        if(isset($this->_timerBindings[$id])) {
            $this->removeTimer($binding);
        }

        $this->_timerBindings[$id] = $binding;

        if($frozen) {
            $binding->isFrozen = true;
        } else {
            $this->_registerTimerBinding($binding);
        }

        return $this;
    }

    abstract protected function _registerTimerBinding(ITimerBinding $binding);
    abstract protected function _unregisterTimerBinding(ITimerBinding $binding);


    public function freezeTimer($binding) {
        if(!$binding instanceof ITimerBinding) {
            $binding = $this->getTimerBinding($binding);
        }
        
        $this->freezeBinding($binding);
        return $this;
    }

    public function freezeAllTimers() {
        foreach($this->_timerBindings as $id => $binding) {
            $this->freezeBinding($binding);
        }

        return $this;
    }

    public function unfreezeTimer($binding) {
        if(!$binding instanceof ITimerBinding) {
            $binding = $this->getTimerBinding($binding);
        }
        
        $this->unfreezeBinding($binding);
        return $this;   
    }

    public function unfreezeAllTimers() {
        foreach($this->_timerBindings as $id => $binding) {
            $this->unfreezeBinding($binding);
        }

        return $this;
    }


    public function removeTimer($binding) {
        if(!$binding instanceof ITimerBinding) {
            $binding = $this->getTimerBinding($binding);
        }
        
        $id = $binding->getId();
        $this->_unregisterTimerBinding($binding);
        unset($this->_timerBindings[$id]);

        return $this;
    }

    public function removeAllTimers() {
        foreach($this->_timerBindings as $id => $binding) {
            $this->_unregisterTimerBinding($binding);
            unset($this->_timerBindings[$id]);
        }

        return $this;
    }


    public function getTimerBinding($id) {
        if($id instanceof ITimerBinding) {
            $id = $id->getId();
        }

        if(!isset($this->_timerBindings[$id])) {
            throw new RuntimeException(
                'Timer binding \''.$id.'\' could not be found'
            );
        }

        return $this->_timerBindings[$id];
    }

    public function countTimerBindings() {
        return count($this->_timerBindings);
    }

    public function getTimerBindings() {
        return $this->_timerBindings;
    }
} 