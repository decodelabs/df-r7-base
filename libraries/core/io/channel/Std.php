<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\channel;

use df;
use df\core;
    
class Std implements core\io\IChannel {

    use core\io\TReader;
    use core\io\TWriter;

    public function writeError($error) {
        fwrite(STDERR, $error);
        return $this;
    }

    public function writeErrorLine($line) {
        return $this->writeError($line."\r\n");
    }


    protected function _readChunk($length) {
        return fread(STDIN, $length);
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