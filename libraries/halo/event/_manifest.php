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


// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class BindException extends RuntimeException {}


// Constants
interface IIoState {
    const READ = 'r';
    const WRITE = 'w';
    const READ_WRITE = 'rw';
    const TIMEOUT = 't';
}



/*************
 * Interfaces
 */ 

// Dispatcher
interface IDispatcher {
    public function listen();
    public function isListening();
    public function stop();


// Global
    public function freezeBinding(IBinding $binding);
    public function unfreezeBinding(IBinding $binding);

    /*
    public function freezeAllBindings();
    public function unfreezeAllBindings();
    public function unbindAll();
    public function getAllBindings();
    public function countAllBindings();
    */
    

// Cycle
    public function setCycleHandler($callback=null);
    public function getCycleHandler();


// Socket
    public function bindSocketRead($id, link\socket\ISocket $socket, $callback);
    public function bindFrozenSocketRead($id, link\socket\ISocket $socket, $callback);
    public function bindSocketReadOnce($id, link\socket\ISocket $socket, $callback);
    public function bindFrozenSocketReadOnce($id, link\socket\ISocket $socket, $callback);
    public function bindSocketWrite($id, link\socket\ISocket $socket, $callback);
    public function bindFrozenSocketWrite($id, link\socket\ISocket $socket, $callback);
    public function bindSocketWriteOnce($id, link\socket\ISocket $socket, $callback);
    public function bindFrozenSocketWriteOnce($id, link\socket\ISocket $socket, $callback);


    public function setSocketTimeout($id, $duration, $callback);
    public function getSocketTimeoutDuration($id);
    public function getSocketTimeoutHandler($id);
    public function removeSocketTimeout($id);

    public function freezeSocket($id);
    public function freezeAllSockets();
    public function unfreezeSocket($id);
    public function unfreezeAllSockets();

    public function unbindSocket($id);
    public function unbindAllSockets();
    
    public function getSocketBinding($id);
    public function countSocketBindings(link\socket\ISocket $socket=null);
    public function getSocketBindings();
    public function countSocketReadBindings();
    public function getSocketReadBindings();
    public function countSocketWriteBindings();
    public function getSocketWriteBindings();


// Stream
    public function bindStreamRead($id, core\io\IStreamChannel $stream, $callback);
    public function bindFrozenStreamRead($id, core\io\IStreamChannel $stream, $callback);
    public function bindStreamReadOnce($id, core\io\IStreamChannel $stream, $callback);
    public function bindFrozenStreamReadOnce($id, core\io\IStreamChannel $stream, $callback);
    public function bindStreamWrite($id, core\io\IStreamChannel $stream, $callback);
    public function bindFrozenStreamWrite($id, core\io\IStreamChannel $stream, $callback);
    public function bindStreamWriteOnce($id, core\io\IStreamChannel $stream, $callback);
    public function bindFrozenStreamWriteOnce($id, core\io\IStreamChannel $stream, $callback);

    public function setStreamTimeout($id, $duration, $callback);
    public function getStreamTimeoutDuration($id);
    public function getStreamTimeoutHandler($id);
    public function removeStreamTimeout($id);

    public function freezeStream($id);
    public function freezeAllStreams();
    public function unfreezeStream($id);
    public function unfreezeAllStreams();

    public function unbindStream($id);
    public function unbindAllStreams();
    
    public function getStreamBinding($id);
    public function countStreamBindings(core\io\IStreamChannel $stream=null);
    public function getStreamBindings();
    public function countStreamReadBindings();
    public function getStreamReadBindings();
    public function countStreamWriteBindings();
    public function getStreamWriteBindings();


// Signal
    public function bindSignal($id, $signals, $callback);
    public function bindFrozenSignal($id, $signals, $callback);
    public function bindSignalOnce($id, $signals, $callback);
    public function bindFrozenSignalOnce($id, $signals, $callback);

    public function freezeSignal($id);
    public function freezeAllSignals();
    public function unfreezeSignal($id);
    public function unfreezeAllSignals();

    public function unbindSignal($id);
    public function unbindAllSignals();
    
    public function getSignalBinding($id);
    public function countSignalBindings();
    public function getSignalBindings();


// Timer
    public function bindTimer($id, $duration, $callback);
    public function bindFrozenTimer($id, $duration, $callback);
    public function bindTimerOnce($id, $duration, $callback);
    public function bindFrozenTimerOnce($id, $duration, $callback);

    public function freezeTimer($id);
    public function freezeAllTimers();
    public function unfreezeTimer($id);
    public function unfreezeAllTimers();

    public function unbindTimer($id);
    public function unbindAllTimers();
    
    public function getTimerBinding($id);
    public function countTimerBindings();
    public function getTimerBindings();
}


interface IDispatcherProvider {
    public function setDispatcher(halo\event\IDispatcher $dispatcher);
    public function getDispatcher();
    public function isRunning();
}


trait TDispatcherProvider {

    protected $events;

    public function setDispatcher(halo\event\IDispatcher $dispatcher) {
        if($this->isRunning()) {
            throw new RuntimeException(
                'You cannot change the dispatcher once the peer has started'
            );
        }

        $this->events = $dispatcher;
        return $this;
    }

    public function getDispatcher() {
        if(!$this->events) {
            $this->events = halo\event\Dispatcher::factory();
        }

        return $this->events;
    }

    public function isRunning() {
        return $this->events && $this->events->isListening();
    }
}





// Binding
interface IBinding {
    public function getId();
    public function getType();
    public function isPersistent();
    public function getHandler();
    public function getDispatcher();

    public function setEventResource($resource);
    public function getEventResource();

    public function freeze();
    public function unfreeze();
    public function isFrozen();
    public function destroy();

    public function trigger($targetResource);
}

interface ITimeoutBinding extends IBinding {
    public function setTimeout($duration, $callback);
    public function getTimeoutDuration();
    public function getTimeoutHandler();
    public function removeTimeout();
}

interface IIoBinding extends ITimeoutBinding {
    public function getIoMode();
    public function getIoResource();
}

interface ISocketBinding extends IIoBinding {
    public function getSocket();
    public function isStreamBased();
}

interface IStreamBinding extends IIoBinding {
    public function getStream();
}

interface ISignalBinding extends IBinding {
    public function getSignals();
}

interface ITimerBinding extends IBinding {
    public function getDuration();
}


abstract class Binding implements IBinding {

    public $id;
    public $isPersistent = true;
    public $isFrozen = false;
    public $handler;
    public $eventResource;
    public $dispatcher;

    public function __construct(IDispatcher $dispatcher, $id, $isPersistent, $callback) {
        $this->id = $id;
        $this->isPersistent = (bool)$isPersistent;
        $this->handler = mesh\Callback::factory($callback);
        $this->dispatcher = $dispatcher;
    }

    public function getId() {
        return $this->id;
    }

    public function isPersistent() {
        return $this->isPersistent;
    }

    public function getHandler() {
        return $this->handler;
    }

    public function getDispatcher() {
        return $this->dispatcher;
    }

    public function setEventResource($resource) {
        $this->eventResource = $resource;
        return $this;
    }

    public function getEventResource() {
        return $this->eventResource;
    }

    public function freeze() {
        $this->dispatcher->freezeBinding($this);
        return $this;
    }

    public function unfreeze() {
        $this->dispatcher->unfreezeBinding($this);
        return $this;
    }

    public function isFrozen() {
        return $this->isFrozen;
    }
}

trait TTimeoutBinding {

    public $timeoutDuration;
    public $timeoutHandler;

    public function setTimeout($duration, $callback) {
        $this->timeoutDuration = core\time\Duration::factory($duration);
        $this->timeoutHandler = mesh\Callback::factory($callback);

        // TODO: Tell dispatcher!

        return $this;
    }

    public function getTimeoutDuration() {
        return $this->timeoutDuration;
    }

    public function getTimeoutHandler() {
        return $this->timeoutHandler;
    }

    public function removeTimeout() {
        $this->timeoutDuration = null;
        $this->timeoutHandler = null;

        // TODO: Tell dispatcher!

        return $this;
    }
}

trait TIoBinding {

    public $ioMode = IIoState::READ;

    public function getIoMode() {
        return $this->ioMode;
    }
}