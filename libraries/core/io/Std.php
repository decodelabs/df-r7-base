<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io;

use df;
use df\core;
    
class Std implements IMultiplexReaderChannel {

    use TReader;
    use TWriter;

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


    public static function getInputStream() {
        return new Stream(STDIN, 'STDIN');
    }

    public static function getOutputStream() {
        return new Stream(STDOUT, 'STDOUT');
    }

    public static function getErrorStream() {
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