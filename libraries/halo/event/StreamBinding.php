<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event;

use df;
use df\core;
use df\halo;

class StreamBinding extends Binding implements IStreamBinding {
    
    use TTimeoutBinding;
    use TIoBinding;

    public $stream;
    public $streamId;

    public function __construct(IDispatcher $dispatcher, $isPersistent, core\io\IStreamChannel $stream, $ioMode, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        $this->stream = $stream;
        $this->streamId = $stream->getChannelId();
        $this->ioMode = $ioMode;

        parent::__construct($dispatcher, $this->ioMode.':'.$this->streamId, $isPersistent, $callback);

        $this->_setTimeout($timeoutDuration, $timeoutCallback);
    }

    public function getType() {
        return 'Stream';
    }

    public function getStream() {
        return $this->stream;
    }

    public function getIoResource() {
        return $this->stream->getStreamDescriptor();
    }

    public function destroy() {
        $this->dispatcher->removeStreamBinding($this);
        return $this;
    }

    public function trigger($resource) {
        if($this->isFrozen) {
            return;
        }

        $this->handler->invokeArgs([$this->stream, $this]);

        if(!$this->isPersistent) {
            $this->dispatcher->removeStreamBinding($this);
        }

        return $this;
    }

    public function triggerTimeout($resource) {
        if($this->isFrozen) {
            return;
        }

        $this->timeoutHandler->invokeArgs([$this->stream, $this]);

        return $this;
    }
}