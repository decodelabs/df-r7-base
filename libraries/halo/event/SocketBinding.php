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

class SocketBinding extends Binding implements ISocketBinding {
    
    use TTimeoutBinding;
    use TIoBinding;

    public $socket;
    public $socketId;
    public $isStreamBased;

    public function __construct(IDispatcher $dispatcher, $id, $isPersistent, link\socket\ISocket $socket, $ioMode, $callback) {
        parent::__construct($dispatcher, $id, $isPersistent, $callback);

        $this->socket = $socket;
        $this->socketId = $socket->getId();
        $this->isStreamBased = $this->socket->getImplementationName() == 'streams';
        $this->ioMode = $ioMode;
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
        $this->dispatcher->unbindSocket($this);
        return $this;
    }

    public function trigger($resource) {
        if($this->isFrozen) {
            return;
        }

        $this->handler->invokeArgs([$this->socket, $this]);

        if(!$this->isPersistent) {
            $this->dispatcher->unbindSocket($this);
        }

        return $this;
    }
}