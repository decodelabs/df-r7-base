<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event;

use df;
use df\core;
use df\halo;


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
    public function start();
    public function stop();
    public function isRunning();
    
    public function setCycleHandler(Callable $callback=null);
    public function getCycleHandler();

    public function newSocketHandler(halo\net\socket\ISocket $socket);
    public function getSocketHandler(halo\net\socket\ISocket $socket);
    public function removeSocket(halo\net\socket\ISocket $socket);

    public function newStreamHandler(core\io\IStreamChannel $stream); 
    public function getStreamHandler(core\io\IStreamChannel $stream);
    public function removeStream(core\io\IStreamChannel $stream);

    public function getHandlers();
    public function countHandlers();
    public function removeHandler(IHandler $handler);
    public function removeAllHandlers();

    public function setSignalHandler($signals, Callable $callback);
    public function hasSignalHandler($signal);
    public function getSignalHandler($signal);
    public function removeSignalHandler($signals);
    
    public function setTimer($id, $duration, Callable $callback);
    public function setTimeout($id, $duration, Callable $callback);
    public function hasTimer($id);
    public function getTimer($id);
    public function getTimerDuration($id);
    public function removeTimer($id);
}


interface IDispatcherProvider {
    public function setDispatcher(halo\event\IDispatcher $dispatcher);
    public function getDispatcher();
    public function isRunning();
}


trait TDispatcherProvider {

    protected $_dispatcher;

    public function setDispatcher(halo\event\IDispatcher $dispatcher) {
        if($this->isRunning()) {
            throw new RuntimeException(
                'You cannot change the dispatcher once the peer has started'
            );
        }

        $this->_dispatcher = $dispatcher;
        return $this;
    }

    public function getDispatcher() {
        if(!$this->_dispatcher) {
            $this->_dispatcher = halo\event\Dispatcher::factory();
        }

        return $this->_dispatcher;
    }

    public function isRunning() {
        return $this->_dispatcher && $this->_dispatcher->isRunning();
    }
}


// Listener
interface IListener {}
interface IAdaptiveListener extends IListener {
    
    public function handleEvent(IHandler $handler, IBinding $binding);
}


class Timer {

    public $id;
    public $duration;
    public $callback;
    public $isPersistent = true;

    public function __construct($id, $duration, Callable $callback, $isPersistent=false) {
        $this->id = $id;
        $this->duration = core\time\Duration::factory($duration);
        $this->callback = $callback;
        $this->isPersistent = (bool)$isPersistent;
    }
}


// Event
interface IHandler {
    public function getId();
    public function getScheme();
    public function getDispatcher();
    
    // Bindings
    public function bind($listener, $bindingName, array $args=null);
    public function bindPersistent($listener, $bindingName, array $args=null);
    public function rebind(IBinding $binding);
    public function unbind(IBinding $binding);
    public function unbindByName($listener, $bindingName, $type=halo\event\IIoState::READ);
    public function unbindAll($listener);
    
    public function getBinding($listener, $bindingName, $type=halo\event\IIoState::READ);
    public function getBindings();
    public function clearBindings();
    public function countBindings();
    
    public function freeze(IBinding $binding);
    public function unfreeze(IBinding $binding);
    
    public function destroy();
}

trait THandler {

    protected $_bindings = array();
    protected $_dispatcher;
    
    public function getScheme() {
        if($this instanceof ISocketHandler) {
            return 'socket';
        } else if($this instanceof IStreamHandler) {
            return 'stream';
        } else if($this instanceof ISignalHandler) {
            return 'signal';
        } else if($this instanceof ITimerHandler) {
            return 'timer';
        }
        
        throw new InvalidArgumentException(
            'Unknown event scheme - '.get_class($this)
        );
    }
    
    public function getDispatcher() {
        return $this->_dispatcher;
    }
    
    
// Bindings
    public function bind($listener, $bindingName, array $args=null) {
        return $this->_bind(new Binding($this, $listener, IIoState::READ, $bindingName, false, $args));
    }

    public function bindPersistent($listener, $bindingName, array $args=null) {
        return $this->_bind(new Binding($this, $listener, IIoState::READ, $bindingName, true, $args));
    }
    
    public function rebind(IBinding $binding) {
        return $this->_bind($binding);
    }
    
    protected function _bind(IBinding $binding) {
        if($binding->isAttached()) {
            throw new BindException(
                'This binding appears to already be assigned'
            );
        }
        
        $id = $binding->getId();
        
        if(isset($this->_bindings[$id])) {
            throw new BindException(
                'Binding '.$id.' has already been created'
            );
        }
        
        $this->_registerBinding($binding);
        $binding->isAttached(true);
        $this->_bindings[$id] = $binding;
        
        return $binding;
    }
    
    abstract protected function _registerBinding(IBinding $binding);
    
    
    public function unbind(IBinding $binding) {
        $id = $binding->getId();
        
        if(isset($this->_bindings[$id])) {
            $this->_unregisterBinding($binding);
            unset($this->_bindings[$id]);
            $binding->setEventResource(null)->isAttached(false);
        }
        
        return $this;
    }
    
    public function unbindByName($listener, $bindingName, $type=halo\event\IIoState::READ) {
        $id = Binding::createId($listener, $type, $bindingName);
        
        if(isset($this->_bindings[$id])) {
            return $this->unbind($this->_bindings[$id]);
        }
        
        return $this;
    }
    
    public function unbindAll($listener) {
        foreach($this->_bindings as $binding) {
            if($binding->hasListener($listener)) {
                $this->unbind($binding);
            }
        }
        
        return $this;
    }
    
    abstract protected function _unregisterBinding(IBinding $binding);
    
    
    public function getBinding($listener, $bindingName, $type=halo\event\IIoState::READ) {
        $id = Binding::createId($listener, $type, ucfirst($bindingName));
        
        if(isset($this->_bindings[$id])) {
            return $this->_bindings[$id];
        }
        
        throw new BindException(
            'Binding '.$id.' could not be found'
        );
    }
    
    public function getBindings() {
        return $this->_bindings;
    }
    
    public function clearBindings() {
        foreach($this->_bindings as $binding) {
            $this->unbind($binding);
        }
        
        return $this;
    }
    
    public function countBindings() {
        return count($this->_bindings);
    }
    
    public function destroy() {
        $this->_dispatcher->remove($this);
        return $this;
    }
}


interface ITimeoutHandler extends IHandler {
    public function setTimeout(core\time\IDuration $time);
    public function getTimeout();
    public function hasTimeout();
    public function bindTimeout($listener, $bindingName, array $args=null);
    public function bindTimeoutPersistent($listener, $bindingName, array $args=null);
}


trait TTimeoutHandler {

    public function bindTimeout($listener, $bindingName, array $args=null) {
        return $this->_bind(new Binding($this, $listener, IIoState::TIMEOUT, $bindingName, false, $args));
    }

    public function bindTimeoutPersistent($listener, $bindingName, array $args=null) {
        return $this->_bind(new Binding($this, $listener, IIoState::TIMEOUT, $bindingName, true, $args));
    }
    
    public function setTimeout(core\time\IDuration $time) {
        core\stub();
    }
    
    public function getTimeout() {
        core\stub();
    }
    
    public function hasTimeout() {
        core\stub();
    }   
}


interface IIoHandler extends ITimeoutHandler {
    public function newBuffer();
    public function bindWrite($listener, $bindingName, array $args=null);
    public function bindWritePersistent($listener, $bindingName, array $args=null);
}

trait TIoHandler {

    public function newBuffer() {
        core\stub();
    }
    
    public function bindWrite($listener, $bindingName, array $args=null) {
        return $this->_bind(new Binding($this, $listener, IIoState::WRITE, $bindingName, false, $args));
    }

    public function bindWritePersistent($listener, $bindingName, array $args=null) {
        return $this->_bind(new Binding($this, $listener, IIoState::WRITE, $bindingName, true, $args));
    }
}

interface ISocketHandler extends IIoHandler {
    public function getSocket();
}

trait TSocketHandler {

    use THandler;
    use TIoHandler;
    use TTimeoutHandler;

    protected $_socket;

    public function __construct(IDispatcher $dispatcher, halo\net\socket\ISocket $socket) {
        $this->_dispatcher = $dispatcher;
        $this->_socket = $socket;
    }

    public function getId() {
        return 'socket:'.$this->_socket->getId();
    }

    public function getSocket() {
        return $this->_socket;
    }
}

interface IStreamHandler extends IIoHandler {
    public function getStream();
}

trait TStreamHandler {

    use THandler;
    use TIoHandler;
    use TTimeoutHandler;

    protected $_stream;
    
    public function __construct(IDispatcher $dispatcher, core\io\IStreamChannel $stream) {
        $this->_dispatcher = $dispatcher;
        $this->_stream = $stream;
    }
    
    public function getId() {
        return $this->_stream->getChannelId();
    }
    
    public function getStream() {
        return $this->_stream;
    }
}


// Binding
interface IBinding {
    public function getId();
    public function getName();
    public function hasName($name);
    public function isAttached($flag=null);
    public function getType();
    public function getArgs();
    public function getListener();
    public function hasListener($listener);
    public function isPersistent();
    public function setEventResource($resource);
    public function getEventResource();
    public function trigger(IHandler $handler);
}



// Buffer
interface IBuffer {
    public function bindRead($listener, $bindingName, $lowMark=0, $highMark=0xffffff, array $args=null);
    public function bindWrite($listener, $bindingName, $lowMark=0, $highMark=0xffffff, array $args=null);
    public function bindError($listener, $bindingName, array $args=null);
    public function setTimeout($time);
    public function setPriority($priority);
    
    public function enable();
    public function disable();
    public function isEnabled();
    public function destroy();
}


interface ISocketBuffer extends IBuffer {
    public function getSocket();
}


interface IStreamBuffer extends IBuffer {
    public function getStream();
}