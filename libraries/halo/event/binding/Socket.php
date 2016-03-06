<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\event\binding;

use df;
use df\core;
use df\halo;
use df\link;

class Socket extends Base implements halo\event\ISocketBinding {

    use halo\event\TTimeoutBinding;
    use halo\event\TIoBinding;

    public $socket;
    public $socketId;
    public $isStreamBased;

    public function __construct(halo\event\IDispatcher $dispatcher, $isPersistent, link\socket\ISocket $socket, $ioMode, $callback, $timeoutDuration=null, $timeoutCallback=null) {
        $this->socket = $socket;
        $this->socketId = $socket->getId();
        $this->isStreamBased = $this->socket->getImplementationName() == 'streams';
        $this->ioMode = $ioMode;

        parent::__construct($dispatcher, $this->ioMode.':'.$this->socketId, $isPersistent, $callback);

        $this->_setTimeout($timeoutDuration, $timeoutCallback);
    }

    public function getType() {
        return 'Socket';
    }

    public function getSocket() {
        return $this->socket;
    }

    public function isStreamBased() {
        return $this->isStreamBased;
    }

    public function getIoResource() {
        return $this->socket->getSocketDescriptor();
    }

    public function destroy() {
        $this->dispatcher->removeSocketBinding($this);
        return $this;
    }

    public function trigger($resource) {
        if($this->isFrozen) {
            return;
        }

        $this->handler->invoke($this->socket, $this);

        if(!$this->isPersistent) {
            $this->dispatcher->removeSocketBinding($this);
        }

        return $this;
    }

    public function triggerTimeout($resource) {
        if($this->isFrozen) {
            return;
        }

        if($this->timeoutHandler) {
            $this->timeoutHandler->invoke($this->socket, $this);
        }

        return $this;
    }
}