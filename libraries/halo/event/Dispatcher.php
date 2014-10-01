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

abstract class Dispatcher implements IDispatcher {
    
    protected $_isListening = false;
    protected $_cycleHandler;
    protected $_socketBindings = [];
    protected $_socketIndex = [];
    protected $_streamBindings = [];
    protected $_streamIndex = [];
    protected $_signalBindings = [];
    protected $_timerBindings = [];

    // DELETE ME
    protected $_handlers = []; 


    public static function factory() {
        if(extension_loaded('libevent')) {
            return new halo\event\libevent\Dispatcher();
        }
        
        return new halo\event\select\Dispatcher();
    }


    public function isListening() {
        return $this->_isListening;
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
    public function bindSocketRead($id, link\socket\ISocket $socket, $callback) {
        return $this->_addSocketBinding(new SocketBinding($this, $id, true, $socket, IIoState::READ, $callback), false);
    }

    public function bindFrozenSocketRead($id, link\socket\ISocket $socket, $callback) {
        return $this->_addSocketBinding(new SocketBinding($this, $id, true, $socket, IIoState::READ, $callback), true);
    }

    public function bindSocketReadOnce($id, link\socket\ISocket $socket, $callback) {
        return $this->_addSocketBinding(new SocketBinding($this, $id, false, $socket, IIoState::READ, $callback), false);
    }

    public function bindFrozenSocketReadOnce($id, link\socket\ISocket $socket, $callback) {
        return $this->_addSocketBinding(new SocketBinding($this, $id, false, $socket, IIoState::READ, $callback), true);
    }

    public function bindSocketWrite($id, link\socket\ISocket $socket, $callback) {
        return $this->_addSocketBinding(new SocketBinding($this, $id, true, $socket, IIoState::WRITE, $callback), false);
    }

    public function bindFrozenSocketWrite($id, link\socket\ISocket $socket, $callback) {
        return $this->_addSocketBinding(new SocketBinding($this, $id, true, $socket, IIoState::WRITE, $callback), true);
    }

    public function bindSocketWriteOnce($id, link\socket\ISocket $socket, $callback) {
        return $this->_addSocketBinding(new SocketBinding($this, $id, false, $socket, IIoState::WRITE, $callback), false);
    }

    public function bindFrozenSocketWriteOnce($id, link\socket\ISocket $socket, $callback) {
        return $this->_addSocketBinding(new SocketBinding($this, $id, false, $socket, IIoState::WRITE, $callback), true);
    }

    protected function _addSocketBinding(ISocketBinding $binding, $frozen) {
        $id = $binding->getId();

        if(isset($this->_socketBindings[$id])) {
            $this->unbindSocket($binding);
        }

        $this->_socketBindings[$id] = $binding;
        $this->_socketIndex[$binding->socketId][$id] = $binding;

        if($frozen) {
            $binding->isFrozen = true;
        } else {
            $this->_registerSocketBinding($binding);
        }

        return $this;
    }

    abstract protected function _registerSocketBinding(ISocketBinding $binding);
    abstract protected function _unregisterSocketBinding(ISocketBinding $binding);


    public function setSocketTimeout($id, $duration, $callback) {
        $this->getSocketBinding($id)->setTimeout($duration, $callback);
        return $this;
    }

    public function getSocketTimeoutDuration($id) {
        return $this->getSocketBinding($id)->getTimeoutDuration();
    }

    public function getSocketTimeoutHandler($id) {
        return $this->getSocketBinding($id)->getTimeoutHandler();
    }

    public function removeSocketTimeout($id) {
        $this->getSocketBinding($id)->removeTimeout();
        return $this;
    }


    public function freezeSocket($binding) {
        if($binding instanceof link\socket\ISocket) {
            $socket = $binding;

            foreach($this->_socketBindings as $id => $binding) {
                if($binding->socket === $socket) {
                    $this->freezeSocket($binding);
                }
            }

            return $this;
        }


        if(!$binding instanceof ISocketBinding) {
            $binding = $this->getSocketBinding($binding);
        }
        
        $this->freezeBinding($binding);
        return $this;
    }

    public function freezeAllSockets() {
        foreach($this->_socketBindings as $id => $binding) {
            $this->freezeBinding($binding);
        }

        return $this;
    }

    public function unfreezeSocket($binding) {
        if($binding instanceof link\socket\ISocket) {
            $socket = $binding;

            foreach($this->_socketBindings as $id => $binding) {
                if($binding->socket === $socket) {
                    $this->unfreezeSocket($binding);
                }
            }

            return $this;
        }


        if(!$binding instanceof ISocketBinding) {
            $binding = $this->getSocketBinding($binding);
        }
        
        $this->unfreezeBinding($binding);
        return $this;
    }

    public function unfreezeAllSockets() {
        foreach($this->_socketBindings as $id => $binding) {
            $this->unfreezeBinding($binding);
        }

        return $this;
    }


    public function unbindSocket($binding) {
        if($binding instanceof link\socket\ISocket) {
            $socket = $binding;

            foreach($this->_socketBindings as $id => $binding) {
                if($binding->socket === $socket) {
                    $this->unbindSocket($binding);
                }
            }

            return $this;
        }


        if(!$binding instanceof ISocketBinding) {
            $binding = $this->getSocketBinding($binding);
        }
        
        $this->_unregisterSocketBinding($binding);

        unset(
            $this->_socketBindings[$binding->id],
            $this->_socketIndex[$binding->socketId][$binding->id]
        );

        return $this;
    }

    public function unbindAllSockets() {
        foreach($this->_socketBindings as $id => $binding) {
            $this->_unregisterSocketBinding($binding);

            unset(
                $this->_socketBindings[$id],
                $this->_socketIndex[$binding->socketId][$id]
            );
        }

        return $this;
    }

    
    public function getSocketBinding($id) {
        if($id instanceof ISocketBinding) {
            $id = $id->getId();
        }

        if(!isset($this->_socketBindings[$id])) {
            throw new RuntimeException(
                'Socket binding \''.$id.'\' could not be found'
            );
        }

        return $this->_socketBindings[$id];
    }

    public function countSocketBindings(link\socket\ISocket $socket=null) {
        if(!$socket) {
            return count($this->_socketBindings);
        }

        $id = $socket->getId();

        if(!isset($this->_socketIndex[$id])) {
            return 0;
        }

        return count($this->_socketIndex[$id]);
    }

    public function getSocketBindings() {
        return $this->_socketBindings;
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
    public function bindStreamRead($id, core\io\IStreamChannel $stream, $callback) {
        return $this->_addStreamBinding(new StreamBinding($this, $id, true, $stream, IIoState::READ, $callback), false);
    }

    public function bindFrozenStreamRead($id, core\io\IStreamChannel $stream, $callback) {
        return $this->_addStreamBinding(new StreamBinding($this, $id, true, $stream, IIoState::READ, $callback), true);
    }

    public function bindStreamReadOnce($id, core\io\IStreamChannel $stream, $callback) {
        return $this->_addStreamBinding(new StreamBinding($this, $id, false, $stream, IIoState::READ, $callback), false);
    }

    public function bindFrozenStreamReadOnce($id, core\io\IStreamChannel $stream, $callback) {
        return $this->_addStreamBinding(new StreamBinding($this, $id, false, $stream, IIoState::READ, $callback), true);
    }

    public function bindStreamWrite($id, core\io\IStreamChannel $stream, $callback) {
        return $this->_addStreamBinding(new StreamBinding($this, $id, true, $stream, IIoState::WRITE, $callback), false);
    }

    public function bindFrozenStreamWrite($id, core\io\IStreamChannel $stream, $callback) {
        return $this->_addStreamBinding(new StreamBinding($this, $id, true, $stream, IIoState::WRITE, $callback), true);
    }

    public function bindStreamWriteOnce($id, core\io\IStreamChannel $stream, $callback) {
        return $this->_addStreamBinding(new StreamBinding($this, $id, false, $stream, IIoState::WRITE, $callback), false);
    }

    public function bindFrozenStreamWriteOnce($id, core\io\IStreamChannel $stream, $callback) {
        return $this->_addStreamBinding(new StreamBinding($this, $id, false, $stream, IIoState::WRITE, $callback), true);
    }

    protected function _addStreamBinding(IStreamBinding $binding, $frozen) {
        $id = $binding->getId();

        if(isset($this->_streamBindings[$id])) {
            $this->unbindStream($binding);
        }

        $this->_streamBindings[$id] = $binding;
        $this->_streamIndex[$binding->streamId][$id] = $binding;

        if($frozen) {
            $binding->isFrozen = true;
        } else {
            $this->_registerStreamBinding($binding);
        }

        return $this;
    }

    abstract protected function _registerStreamBinding(IStreamBinding $binding);
    abstract protected function _unregisterStreamBinding(IStreamBinding $binding);


    public function setStreamTimeout($id, $duration, $callback) {
        $this->getStreamBinding($id)->setTimeout($duration, $callback);
        return $this;
    }

    public function getStreamTimeoutDuration($id) {
        return $this->getStreamBinding($id)->getTimeoutDuration();
    }

    public function getStreamTimeoutHandler($id) {
        return $this->getStreamBinding($id)->getTimeoutHandler();
    }

    public function removeStreamTimeout($id) {
        $this->getStreamBinding($id)->removeTimeout();
        return $this;
    }


    public function freezeStream($binding) {
        if($binding instanceof core\io\IStreamChannel) {
            $stream = $binding;

            foreach($this->_streamBindings as $id => $binding) {
                if($binding->stream === $stream) {
                    $this->freezeStream($binding);
                }
            }

            return $this;
        }


        if(!$binding instanceof IStreamBinding) {
            $binding = $this->getStreamBinding($binding);
        }
        
        $this->freezeBinding($binding);
        return $this;
    }

    public function freezeAllStreams() {
        foreach($this->_streamBindings as $id => $binding) {
            $this->freezeBinding($binding);
        }

        return $this;
    }

    public function unfreezeStream($binding) {
        if($binding instanceof core\io\IStreamChannel) {
            $stream = $binding;

            foreach($this->_streamBindings as $id => $binding) {
                if($binding->stream === $stream) {
                    $this->unfreezeStream($binding);
                }
            }

            return $this;
        }


        if(!$binding instanceof IStreamBinding) {
            $binding = $this->getStreamBinding($binding);
        }
        
        $this->unfreezeBinding($binding);
        return $this;
    }

    public function unfreezeAllStreams() {
        foreach($this->_streamBindings as $id => $binding) {
            $this->unfreezeBinding($binding);
        }

        return $this;
    }


    public function unbindStream($binding) {
        if($binding instanceof core\io\IStreamChannel) {
            $stream = $binding;

            foreach($this->_streamBindings as $id => $binding) {
                if($binding->stream === $stream) {
                    $this->unbindStream($binding);
                }
            }

            return $this;
        }


        if(!$binding instanceof IStreamBinding) {
            $binding = $this->getStreamBinding($binding);
        }
        
        $this->_unregisterStreamBinding($binding);

        unset(
            $this->_streamBindings[$binding->id],
            $this->_streamIndex[$binding->streamId][$id]
        );

        return $this;
    }

    public function unbindAllStreams() {
        foreach($this->_streamBindings as $id => $binding) {
            $this->_unregisterStreamBinding($binding);

            unset(
                $this->_streamBindings[$id],
                $this->_streamIndex[$binding->streamId][$id]
            );
        }

        return $this;
    }

    
    public function getStreamBinding($id) {
        if($id instanceof IStreamBinding) {
            $id = $id->getId();
        }

        if(!isset($this->_streamBindings[$id])) {
            throw new RuntimeException(
                'Stream binding \''.$id.'\' could not be found'
            );
        }

        return $this->_streamBindings[$id];
    }

    public function countStreamBindings(core\io\IStreamChannel $stream=null) {
        if(!$stream) {
            return count($this->_streamBindings);
        }

        $id = $stream->getChannelId();

        if(!isset($this->_streamIndex[$id])) {
            return 0;
        }

        return count($this->_streamIndex[$id]);
    }

    public function getStreamBindings() {
        return $this->_streamBindings;
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
        return $this->_addSignalBinding(new SignalBinding($this, $id, true, $signals, $callback), false);
    }

    public function bindFrozenSignal($id, $signals, $callback) {
        return $this->_addSignalBinding(new SignalBinding($this, $id, true, $signals, $callback), true);
    }

    public function bindSignalOnce($id, $signals, $callback) {
        return $this->_addSignalBinding(new SignalBinding($this, $id, false, $signals, $callback), false);
    }

    public function bindFrozenSignalOnce($id, $signals, $callback) {
        return $this->_addSignalBinding(new SignalBinding($this, $id, false, $signals, $callback), true);
    }

    protected function _addSignalBinding(ISignalBinding $binding, $frozen) {
        $id = $binding->getId();

        if(isset($this->_signalBindings[$id])) {
            $this->unbindSignal($binding);
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


    public function freezeSignal($binding) {
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

    public function unfreezeSignal($binding) {
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


    public function unbindSignal($binding) {
        if(!$binding instanceof ISignalBinding) {
            $binding = $this->getSignalBinding($binding);
        }
        
        $id = $binding->getId();
        $this->_unregisterSignalBinding($binding);
        unset($this->_signalBindings[$id]);

        return $this;
    }

    public function unbindAllSignals() {
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
        return $this->_addTimerBinding(new TimerBinding($this, $id, true, $duration, $callback), false);
    }

    public function bindFrozenTimer($id, $duration, $callback) {
        return $this->_addTimerBinding(new TimerBinding($this, $id, true, $duration, $callback), true);
    }

    public function bindTimerOnce($id, $duration, $callback) {
        return $this->_addTimerBinding(new TimerBinding($this, $id, false, $duration, $callback), false);
    }

    public function bindFrozenTimerOnce($id, $duration, $callback) {
        return $this->_addTimerBinding(new TimerBinding($this, $id, false, $duration, $callback), true);
    }

    protected function _addTimerBinding(ITimerBinding $binding, $frozen) {
        $id = $binding->getId();

        if(isset($this->_timerBindings[$id])) {
            $this->unbindTimer($binding);
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


    public function unbindTimer($binding) {
        if(!$binding instanceof ITimerBinding) {
            $binding = $this->getTimerBinding($binding);
        }
        
        $id = $binding->getId();
        $this->_unregisterTimerBinding($binding);
        unset($this->_timerBindings[$id]);

        return $this;
    }

    public function unbindAllTimers() {
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






// Handlers - DELETE ME
    public function getHandlers() {
        return $this->_handlers;
    }
    
    public function countHandlers() {
        return count($this->_handlers);
    }

    public function removeHandler(IHandler $handler) {
        $id = $handler->getId();
        
        if(isset($this->_handlers[$id])) {
            $handler->clearBindings();
            unset($this->_handlers[$id]);
        }
        
        return $this;
    }
    
    
    public function removeAllHandlers() {
        foreach($this->_handlers as $handler) {
            $this->removeHandler($handler);
        }

        return $this;
    }
    
    protected function _registerHandler(IHandler $handler) {
        $id = $handler->getId();
        
        if(isset($this->_handlers[$id])) {
            throw new RuntimeException(
                'Event '.$id.' has already been registered'
            );
        }
        
        $this->_handlers[$id] = $handler;
        return $handler;
    }
} 