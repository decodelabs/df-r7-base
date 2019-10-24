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

use DecodeLabs\Glitch;

// Exceptions
interface IException
{
}
class InvalidArgumentException extends \InvalidArgumentException implements IException
{
}
class RuntimeException extends \RuntimeException implements IException
{
}
class BindException extends RuntimeException
{
}


// Constants
interface IIoState
{
    const READ = 'r';
    const WRITE = 'w';
}


// Dispatcher
interface IDispatcher
{
    public function listen();
    public function isListening();
    public function stop();


    // Global
    public function freezeBinding(IBinding $binding);
    public function unfreezeBinding(IBinding $binding);

    public function freezeAllBindings();
    public function unfreezeAllBindings();
    public function removeAllBindings();
    public function getAllBindings();
    public function countAllBindings();


    // Cycle
    public function setCycleHandler($callback=null);
    public function getCycleHandler();


    // Socket
    public function bindSocketRead(link\Socket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindFrozenSocketRead(link\Socket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindSocketReadOnce(link\Socket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindFrozenSocketReadOnce(link\Socket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindSocketWrite(link\Socket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindFrozenSocketWrite(link\Socket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindSocketWriteOnce(link\Socket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindFrozenSocketWriteOnce(link\Socket $socket, $callback, $timeoutDuration=null, $timeoutCallback=null);

    public function freezeSocket(link\Socket $socket);
    public function freezeSocketRead(link\Socket $socket);
    public function freezeSocketWrite(link\Socket $socket);
    public function freezeAllSockets();

    public function unfreezeSocket(link\Socket $socket);
    public function unfreezeSocketRead(link\Socket $socket);
    public function unfreezeSocketWrite(link\Socket $socket);
    public function unfreezeAllSockets();

    public function removeSocket(link\Socket $socket);
    public function removeSocketRead(link\Socket $socket);
    public function removeSocketWrite(link\Socket $socket);
    public function removeSocketBinding(ISocketBinding $binding);
    public function removeAllSockets();

    public function countSocketBindings(link\Socket $socket=null);
    public function getSocketBindings(link\Socket $socket=null);
    public function countSocketReadBindings();
    public function getSocketReadBindings();
    public function countSocketWriteBindings();
    public function getSocketWriteBindings();


    // Stream
    public function bindStreamRead(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindFrozenStreamRead(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindStreamReadOnce(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindFrozenStreamReadOnce(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindStreamWrite(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindFrozenStreamWrite(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindStreamWriteOnce(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null);
    public function bindFrozenStreamWriteOnce(core\io\IStreamChannel $stream, $callback, $timeoutDuration=null, $timeoutCallback=null);

    public function freezeStream(core\io\IStreamChannel $stream);
    public function freezeStreamRead(core\io\IStreamChannel $stream);
    public function freezeStreamWrite(core\io\IStreamChannel $stream);
    public function freezeAllStreams();

    public function unfreezeStream(core\io\IStreamChannel $stream);
    public function unfreezeStreamRead(core\io\IStreamChannel $stream);
    public function unfreezeStreamWrite(core\io\IStreamChannel $stream);
    public function unfreezeAllStreams();

    public function removeStream(core\io\IStreamChannel $stream);
    public function removeStreamRead(core\io\IStreamChannel $stream);
    public function removeStreamWrite(core\io\IStreamChannel $stream);
    public function removeStreamBinding(IStreamBinding $binding);
    public function removeAllStreams();

    public function countStreamBindings(core\io\IStreamChannel $stream=null);
    public function getStreamBindings(core\io\IStreamChannel $stream=null);
    public function countStreamReadBindings();
    public function getStreamReadBindings();
    public function countStreamWriteBindings();
    public function getStreamWriteBindings();


    // Signal
    public function bindSignal($id, $signals, $callback);
    public function bindFrozenSignal($id, $signals, $callback);
    public function bindSignalOnce($id, $signals, $callback);
    public function bindFrozenSignalOnce($id, $signals, $callback);

    public function freezeSignal($signal);
    public function freezeSignalBinding($binding);
    public function freezeAllSignals();

    public function unfreezeSignal($signal);
    public function unfreezeSignalBinding($binding);
    public function unfreezeAllSignals();

    public function removeSignal($signal);
    public function removeSignalBinding($binding);
    public function removeAllSignals();

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

    public function removeTimer($id);
    public function removeAllTimers();

    public function getTimerBinding($id);
    public function countTimerBindings();
    public function getTimerBindings();
}


interface IDispatcherProvider
{
    public function setEventDispatcher(halo\event\IDispatcher $dispatcher);
    public function getEventDispatcher();
    public function isRunning();
}


trait TDispatcherProvider
{
    protected $events;

    public function setEventDispatcher(halo\event\IDispatcher $dispatcher)
    {
        if ($this->isRunning()) {
            throw Glitch::ERuntime(
                'You cannot change the dispatcher once the peer has started'
            );
        }

        $this->events = $dispatcher;
        return $this;
    }

    public function getEventDispatcher()
    {
        if (!$this->events) {
            $this->events = halo\event\Base::factory();
        }

        return $this->events;
    }

    public function isRunning()
    {
        return $this->events && $this->events->isListening();
    }
}





// Binding
interface IBinding
{
    public function getId(): string;
    public function getType();
    public function isPersistent();
    public function getHandler();
    public function getDispatcher();

    public function setEventResource($resource);
    public function getEventResource();

    public function freeze();
    public function unfreeze();
    public function isFrozen();
    public function markFrozen(bool $frozen): IBinding;
    public function destroy();

    public function trigger($targetResource);
}

interface ITimeoutBinding extends IBinding
{
    public function getTimeoutDuration();
    public function getTimeoutHandler();
    public function triggerTimeout($targetResource);
}

interface IIoBinding extends ITimeoutBinding
{
    public function getIoMode();
    public function getIoResource();
}

interface ISocketBinding extends IIoBinding
{
    public function getSocket();
    public function isStreamBased();
}

interface IStreamBinding extends IIoBinding
{
    public function getStream();
}

interface ISignalBinding extends IBinding
{
    public function getSignals();
}

interface ITimerBinding extends IBinding
{
    public function getDuration();
}



trait TTimeoutBinding
{
    public $timeoutDuration;
    public $timeoutHandler;

    protected function _setTimeout($duration, $callback)
    {
        if ($duration !== null) {
            $duration = core\time\Duration::factory($duration);
        }

        if ($callback !== null) {
            $callback = core\lang\Callback::factory($callback);
        }

        $this->timeoutDuration = $duration;
        $this->timeoutHandler = $callback;
    }

    public function getTimeoutDuration()
    {
        return $this->timeoutDuration;
    }

    public function getTimeoutHandler()
    {
        return $this->timeoutHandler;
    }
}

trait TIoBinding
{
    public $ioMode = IIoState::READ;

    public function getIoMode()
    {
        return $this->ioMode;
    }
}
