<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\channel;

use df;
use df\core;
    
class Stream implements core\io\IStreamChannel {

    use core\io\TReader;
    use core\io\TWriter;

    protected $_resource;
    protected $_error = '';
    protected $_isBlocking = true;

    public function __construct($resource) {
        $this->_resource = $resource;
        $this->setBlocking(true);
    }

    public function getChannelId() {
        return 'Stream:'.$this->_resource;
    }

    public function flush() {
        return $this;
    }


// Error
    public function getErrorBuffer() {
        return $this->_error;
    }

    public function flushErrorBuffer() {
        $output = $this->_error;
        $this->_error = null;

        return $output;
    }

    public function writeError($error) {
        $this->_error .= $error;
        return $this;
    }

    public function writeErrorLine($line) {
        return $this->writeError($line."\r\n");
    }


    protected function _readChunk($length) {
        try {
            $output = fread($this->_resource, $length);
        } catch(\Exception $e) {
            return false;
        }

        if($output === ''
        || $output === null
        || $output === false) {
            return false;
        }
        
        return $output;
    }

    protected function _readLine() {
        try {
            $output = fgets($this->_resource);
        } catch(\Exception $e) {
            return false;
        }

        if($output === ''
        || $output === null
        || $output === false) {
            return false;
        }
        
        return $output;
    }

    protected function _writeChunk($data, $length) {
        return fwrite($this->_resource, $data, $length);
    }


// Stream
    public function getStreamDescriptor() {
        return $this->_resource;
    }

    public function setBlocking($flag) {
        stream_set_blocking($this->_resource, (int)((bool)$flag));
        $this->_isBlocking = (bool)$flag;
        return $this;
    }

    public function getBlocking() {
        return $this->_isBlocking;
    }
}