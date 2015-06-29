<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io;

use df;
use df\core;


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}
class OverflowException extends \OverflowException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}



// Reader
interface IChunkSender {
    public function setChunkReceiver(IChunkReceiver $reader);
    public function getChunkReceiver();
    public function sendChunks();
}


interface IReader {
    public function read();
    public function readChunk($length);
    public function readLine();
    public function readChar();

    public function isReadingEnabled();
}


trait TReader {

    public function read() {
        if(!$this->isReadingEnabled()) {
            throw new LogicException(
                'Reading has been shut down'
            );
        }

        $data = false;
        
        while(false !== ($read = $this->_readChunk(1024))) {
            $data .= $read;
        }
        
        return $data;
    }

    public function readChunk($length) {
        if(!$this->isReadingEnabled()) {
            throw new LogicException(
                'Reading has been shut down'
            );
        }
        
        return $this->_readChunk($length);
    }

    public function readLine() {
        if(!$this->isReadingEnabled()) {
            throw new LogicException(
                'Reading has been shut down'
            );
        }

        return rtrim($this->_readLine(), "\r\n");
    }

    public function readChar() {
        return $this->readChunk(1);
    }

    public function isReadingEnabled() {
        return true;
    }

    abstract protected function _readChunk($length);
    abstract protected function _readLine();
}




// Peek reader
interface IPeekReader extends IReader {
    public function peek($length);
}


trait TPeekReader {

    public function peek($length) {
        if(!$this->isReadingEnabled()) {
            throw new LogicException(
                'Reading has been shut down'
            );
        }
        
        return $this->_peekChunk($length);
    }

    abstract protected function _peekChunk($length);
}



// Writer
interface IChunkReceiver {
    public function writeChunk($chunk, $length=null);
}

interface IWriter extends IChunkReceiver {
    public function setWriteCallback($callback);
    public function getWriteCallback();

    public function write($data);
    public function writeLine($line='');
    public function writeBuffer(&$buffer, $length);

    public function isWritingEnabled();
}


trait TWriter {

    private $_writeCallback;

    public function setWriteCallback($callback) {
        if($callback !== null) {
            $callback = core\lang\Callback::factory($callback);
        }

        $this->_writeCallback = $callback;
        return $this;
    }

    public function getWriteCallback() {
        return $this->_writeCallback;
    }

    protected function _triggerWriteCallback() {
        if($this->_writeCallback) {
            if(true !== $this->_writeCallback->invoke($this)) {
                $this->_writeCallback = null;
            }
        }
    }

    public function write($data) {
        if(!$this->isWritingEnabled()) {
            throw new LogicException(
                'Writing has already been shut down'
            );
        }
        
        if(!$length = strlen($data)) {
            return $this;
        }
        
        for($written = 0; $written < $length; $written += $result) {
            if($this->_writeCallback) {
                $this->_triggerWriteCallback();
            }

            $result = $this->_writeChunk(substr($data, $written), $length - $written);
            
            if($result === false) {
                throw new OverflowException(
                    'Unable to write to channel'
                );
            }
        }
        
        return $this;
    }

    public function writeLine($line='') {
        return $this->write($line."\n");
    }
    
    public function writeChunk($data, $length=null) {
        if(!$this->isWritingEnabled()) {
            throw new LogicException(
                'Writing has already been shut down'
            );
        }

        $length = (int)$length;

        if($length <= 0) {
            $length = strlen($data);
        }

        if($this->_writeCallback) {
            $this->_triggerWriteCallback();
        }
        
        return $this->_writeChunk($data, $length);
    }

    public function writeBuffer(&$buffer, $length) {
        $result = $this->writeChunk($buffer, $length);
        $buffer = substr($buffer, $result);
        return $result;
    }

    public function isWritingEnabled() {
        return true;
    }

    
    abstract protected function _writeChunk($data, $length);
}


interface IFlushable {
    public function flush();
}





// Channel
interface IChannel extends IReader, IWriter, IFlushable {
    public function getChannelId();
    public function writeError($error);
    public function writeErrorLine($line);
}

interface IMultiplexReaderChannel extends IChannel {
    public function setReadBlocking($flag);
    public function getReadBlocking();
}

interface IContainedStateChannel extends IChannel {
    public function getErrorBuffer();
    public function flushErrorBuffer();
}

interface IStreamChannel extends IContainedStateChannel {
    public function getStreamDescriptor();
    public function getMetadata();
    public function setBlocking($flag);
    public function getBlocking();
    public function close();
}

// File
interface IMultiplexer extends IFlushable, core\IRegistryObject {
    public function setId($id);
    public function getId();

    public function setLineLevel($level);
    public function getLineLevel();
    public function incrementLineLevel();
    public function decrementLineLevel();

    public function setChannels(array $channels);
    public function addChannels(array $channels);
    public function addChannel(IChannel $channel);
    public function hasChannel($id);
    public function getChannel($id);
    public function getFirstChannel();
    public function removeChannel($id);
    public function getChannels();
    public function clearChannels();

    public function setChunkReceivers(array $receivers);
    public function addChunkReceivers(array $receivers);
    public function addChunkReceiver($id, IChunkReceiver $receiver);
    public function hasChunkReceiver($id);
    public function getChunkReceiver($id);
    public function getChunkReceivers();
    public function clearChunkReceivers();

    public function write($data);
    public function writeLine($line='');
    public function writeError($error);
    public function writeErrorLine($line);

    public function readLine();
    public function readChunk($size);
    public function setReadBlocking($flag);
}






// Accept type
interface IAcceptTypeProcessor {
    public function setAcceptTypes($types=null);
    public function addAcceptTypes($types);
    public function getAcceptTypes();
    public function isTypeAccepted($type);
}

trait TAcceptTypeProcessor {

    protected $_acceptTypes = [];

    public function setAcceptTypes($types=null) {
        $this->_acceptTypes = [];

        if($types === null) {
            return $this;
        }

        return $this->addAcceptTypes(func_get_args());
    }
        
    public function addAcceptTypes($types) {   
        $types = core\collection\Util::flattenArray(func_get_args());

        foreach($types as $type) {
            $type = trim(strtolower($type));
            
            if(!strlen($type)) {
                continue;
            }

            if($type{0} == '.') {
                $type = core\fs\Type::extToMime(substr($type, 1));
            }

            if(false === strpos($type, '/')) {
                $type .= '/*';
            }
            
            if(!in_array($type, $this->_acceptTypes)) {
                $this->_acceptTypes[] = $type;
            }
        }
        
        return $this;
    }
    
    public function getAcceptTypes() {
        return $this->_acceptTypes;
    }

    public function isTypeAccepted($type) {
        if(empty($this->_acceptTypes)) {
            return true;
        }

        if(!strlen($type)) {
            return false;
        }

        if($type{0} == '.') {
            $type = core\fs\Type::extToMime(substr($type, 1));
        }

        @list($category, $name) = explode('/', $type, 2);

        foreach($this->_acceptTypes as $accept) {
            if($accept == '*') {
                return true;
            }

            @list($acceptCategory, $acceptName) = explode('/', $accept, 2);

            if($acceptCategory == '*') {
                return true;
            }

            if($acceptCategory != $category) {
                continue;
            }

            if($acceptName == '*') {
                return true;
            }

            if($acceptName != $name) {
                continue;
            }

            return true;
        }

        return false;
    }
}
