<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\daemons;

use df;
use df\core;
use df\halo;
use df\link;
    
class Angel extends halo\daemon\Base implements link\IServer {

    use link\TPeer_Server;

    const REQUIRES_PRIVILEGED_PROCESS = false;
    const TEST_MODE = true; // delete me

    protected function _setup() {
        $this->_setupPeerServer();
    }


// Communication server
    protected function _createMasterSockets() {
        $this->_registerMasterSocket(
            link\socket\Server::factory('tcp://0.0.0.0:16028')
        );
    }

    protected function _createSessionFromSocket(link\socket\IServerPeerSocket $socket) {
        return new Angel_Session($socket);
    }

    protected function _handleWriteBuffer(link\ISession $session) {
        echo 'write'."\n";
    }

    protected function _handleReadBuffer(link\ISession $session) {
        echo 'read'."\n";
    }

    public function start() {
        core\stub();
    }
}


class Angel_Session implements link\ISession {

    use link\TPeer_Session;
}