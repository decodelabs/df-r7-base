<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\socket\native;

use df;
use df\core;
use df\halo;

    
trait TNative {

    public function getImplementationName() {
        return 'native';
    }

    protected function _shutdownReading() {
        return @socket_shutdown($this->_socket, 0);
    }
    
    protected function _shutdownWriting() {
        return @socket_shutdown($this->_socket, 1);
    }
    
    protected function _closeSocket() {
        return @socket_close($this->_socket);
    }
    
    protected function _getLastErrorMessage() {
        return socket_strerror(socket_last_error());
    }
}

trait TNative_IoSocket {

    protected function _peekChunk($length) {
        if(!socket_recv($this->_socket, $output, $length, MSG_PEEK)) {
            return false;
        }
        
        return $output;
    }
    
    protected function _readChunk($length) {
        if(!socket_recv($this->_socket, $output, $length, MSG_DONTWAIT)) {
            return false;
        }
        
        return $output;
    }
    
    protected function _writeChunk($data) {
        $output = socket_send($this->_socket, $data, strlen($data), 0);
        
        if($output === false
        || $output === 0) {
            return false;
        }
        
        return $output;
    }
}