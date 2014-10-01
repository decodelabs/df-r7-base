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

    public function __construct(IDispatcher $dispatcher, $id, $isPersistent, core\io\IStreamChannel $stream, $ioMode, $callback) {
        parent::__construct($dispatcher, $id, $isPersistent, $callback);

        $this->stream = $stream;
        $this->streamId = $stream->getChannelId();
        $this->ioMode = $ioMode;
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
        $this->dispatcher->unbindStream($this);
        return $this;
    }

    public function trigger($resource) {
        if($this->isFrozen) {
            return;
        }

        $this->handler->invokeArgs([$this->stream, $this]);

        if(!$this->isPersistent) {
            $this->dispatcher->unbindStream($this);
        }

        return $this;
    }
}