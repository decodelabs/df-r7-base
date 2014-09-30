<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\channel;

use df;
use df\core;
    
class Std implements core\io\IMultiplexReaderChannel {

    use core\io\TReader;
    use core\io\TWriter;

    protected $_readBlocking = true;

    public function __construct() {
        $this->setReadBlocking(true);
    }

    public function getChannelId() {
        return 'STD';
    }

    public function flush() {
        return $this;
    }


    public function getInputStream() {
        return new Stream(STDIN, 'STDIN');
    }

    public function getOutputStream() {
        return new Stream(STDOUT, 'STDOUT');
    }

    public function getErrorStream() {
        return new Stream(STDERR, 'STDERR');
    }



    public function writeError($error) {
        fwrite(STDERR, $error);
        return $this;
    }

    public function writeErrorLine($line) {
        return $this->writeError($line."\r\n");
    }


    public function setReadBlocking($flag) {
        stream_set_blocking(STDIN, (int)((bool)$flag));
        $this->_readBlocking = (bool)$flag;
        return $this;
    }

    public function getReadBlocking() {
        return $this->_readBlocking;
    }

    protected function _readChunk($length) {
        try {
            $output = fread(STDIN, $length);
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
            $output = fgets(STDIN);
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
        return fwrite(STDOUT, $data, $length);
    }
}