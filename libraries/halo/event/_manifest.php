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
interface IException extends halo\IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class BindException extends RuntimeException {}


// Constants
const READ = 'r';
const WRITE = 'w';
const READ_WRITE = 'rw';
const TIMEOUT = 't';



/*************
 * Interfaces
 */ 

// Dispatcher
interface IDispatcher {
    public function start();
    public function stop();
    public function isRunning();
    
    public function newSocketHandler(halo\socket\ISocket $socket);
    public function getSocketHandler(halo\socket\ISocket $socket);
    public function newStreamHandler(core\io\stream\IStream $stream); 
    public function getStreamHandler(core\io\stream\IStream $stream);
    public function newSignalHandler($signal);
    public function getSignalHandler($signal);
    public function newTimerHandler(core\time\IDuration $time);
    public function getTimerHandler(core\time\IDuration $time);
    
    public function remove(IHandler $handler);
    public function removeSocket(halo\socket\ISocket $socket);
    public function removeStream(core\io\stream\IStream $stream);
    public function removeSignal($signal);
    public function removeTimer(core\time\IDuration $time);
    public function removeAll();
    
    public function getHandlers();
    public function countHandlers();
}


// Listener
interface IListener {
    
}

interface IAdaptiveListener extends IListener {
    
    public function handleEvent(IHandler $handler, IBinding $binding);
}


// Event
interface IHandler {
    public function getId();
    public function getScheme();
    public function getDispatcher();
    
    // Bindings
    public function bind(IListener $listener, $bindingName, $persistent=false, array $args=null);
    public function rebind(IBinding $binding);
    public function unbind(IBinding $binding);
    public function unbindByName(IListener $listener, $bindingName, $type=halo\event\READ);
    public function unbindAll(IListener $listener);
    
    public function getBinding(IListener $listener, $bindingName, $type=halo\event\READ);
    public function getBindings();
    public function clearBindings();
    public function countBindings();
    
    public function freeze(IBinding $binding);
    public function unfreeze(IBinding $binding);
    
    public function destroy();
}


interface ITimeoutHandler extends IHandler {
    public function setTimeout(core\time\IDuration $time);
    public function getTimeout();
    public function hasTimeout();
    public function bindTimeout(IListener $listener, $bindingName, $persistent=false, array $args=null);
}

interface IIoHandler extends ITimeoutHandler {
    public function newBuffer();
    public function bindWrite(IListener $listener, $bindingName, $persistent=false, array $args=null);
}

interface ISocketHandler extends IIoHandler {
    public function getSocket();
}

interface IStreamHandler extends IIoHandler {
    public function getStream();
}

interface ISignalHandler extends ITimeoutHandler {
    public function getSignal();
}

interface ITimerHandler extends IHandler {
    public function getTime();
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
    public function hasListener(IListener $listener);
    public function isPersistent();
    public function setEventResource($resource);
    public function getEventResource();
    public function trigger(IHandler $handler);
}



// Buffer
interface IBuffer {
    public function bindRead(IListener $listener, $bindingName, $lowMark=0, $highMark=0xffffff, array $args=null);
    public function bindWrite(IListener $listener, $bindingName, $lowMark=0, $highMark=0xffffff, array $args=null);
    public function bindError(IListener $listener, $bindingName, array $args=null);
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