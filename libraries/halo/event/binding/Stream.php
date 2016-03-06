<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\binding;

use df;
use df\core;
use df\halo;

class Stream extends Base implements halo\event\IStreamBinding {

    use halo\event\TTimeoutBinding;
    use halo\event\TIoBinding;

    public $stream;
    public $streamId;

    public function __construct(halo\event\IDispatcher $dispatcher, $isPersistent, core\io\IStreamChannel $stream, $ioMode, $callback, $timeoutDuration=null, $timeoutCallback=null) {
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

        $this->handler->invoke($this->stream, $this);

        if(!$this->isPersistent) {
            $this->dispatcher->removeStreamBinding($this);
        }

        return $this;
    }

    public function triggerTimeout($resource) {
        if($this->isFrozen) {
            return;
        }

        if($this->timeoutHandler) {
            $this->timeoutHandler->invoke($this->stream, $this);
        }

        return $this;
    }
}