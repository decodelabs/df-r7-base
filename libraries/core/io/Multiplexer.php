<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io;

use df;
use df\core;
    
class Multiplexer implements IMultiplexer, core\IDumpable {

    const REGISTRY_KEY = 'multiplexer';

    protected $_id;
    protected $_channels = [];
    protected $_chunkReceivers = [];

    public static function defaultFactory($id=null) {
        if(isset($_SERVER['argv'])) {
            $channel = new core\io\channel\Std();
        } else {
            $channel = new core\io\channel\Memory();
        }

        return new self([$channel], $id);
    }

    public function __construct(array $ioSet=null, $id=null) {
        $this->setId($id);

        if($ioSet !== null) {
            foreach($ioSet as $id => $ioNode) {
                if($ioNode instanceof IChannel) {
                    $this->addChannel($ioNode);
                } else if($ioNode instanceof IChunkReceiver) {
                    $this->addChunkReceiver($id, $ioNode);
                }
            }
        }
    }

    public function setId($id) {
        $this->_id = $id;
        return $this;
    }

    public function getId() {
        return $this->_id;
    }


// Registry
    public function getRegistryObjectKey() {
        $output = static::REGISTRY_KEY;

        if($this->_id) {
            $output .= ':'.$this->_id;
        }

        return $output;
    }

    public function onApplicationShutdown() {}


// Channels
    public function setChannels(array $channels) {
        $this->_channels = [];
        return $this->addChannels($channels);
    }

    public function addChannels(array $channels) {
        foreach($channels as $channel) {
            if($channel instanceof core\io\IChannel) {
                $this->addChannel($channel);
            }
        }

        return $this;
    }

    public function addChannel(core\io\IChannel $channel) {
        $this->_channels[$channel->getChannelId()] = $channel;
        return $this;
    }

    public function hasChannel($id) {
        if($id instanceof core\io\IChannel) {
            $id = $id->getChannelId();
        }

        return isset($this->_channels[$id]);
    }

    public function getChannel($id) {
        if($id instanceof core\io\IChannel) {
            $id = $id->getChannelId();
        }

        if(isset($this->_channels[$id])) {
            return $this->_channels[$id];
        }
    }

    public function removeChannel($id) {
        if($id instanceof core\io\IChannel) {
            $id = $id->getChannelId();
        }

        unset($this->_channels[$id]);
        return $this;
    }

    public function getChannels() {
        return $this->_channels;
    }

    public function clearChannels() {
        $this->_channels = [];
        return $this;
    }


// Chunk receivers
    public function setChunkReceivers(array $receivers) {
        $this->_chunkReceivers = [];
        return $this->addChunkReceivers($receivers);
    }

    public function addChunkReceivers(array $receivers) {
        foreach($receivers as $id => $receiver) {
            if($receiver instanceof IChunkReceiver) {
                $this->addChunkReceiver($id, $receiver);
            }
        }

        return $this;
    }

    public function addChunkReceiver($id, IChunkReceiver $receiver) {
        $this->_chunkReceivers[$id] = $receiver;
        return $this;
    }

    public function hasChunkReceiver($id) {
        return isset($this->_chunkReceivers[$id]);
    }

    public function getChunkReceiver($id) {
        if(isset($this->_chunkReceivers[$id])) {
            return $this->_chunkReceivers[$id];
        }
    }

    public function getChunkReceivers() {
        return $this->_chunkReceivers;
    }

    public function clearChunkReceivers() {
        $this->_chunkReceivers = [];
        return $this;
    }


// IO
    public function flush() {
        foreach($this->_channels as $channel) {
            $channel->flush();
        }

        return $this;
    }

    public function write($data) {
        foreach($this->_channels as $channel) {
            $channel->write($data);
        }

        foreach($this->_chunkReceivers as $receiver) {
            $receiver->writeChunk($data);
        }

        return $this;
    }

    public function writeLine($line='') {
        foreach($this->_channels as $channel) {
            $channel->writeLine($line);
        }

        foreach($this->_chunkReceivers as $receiver) {
            $receiver->writeChunk($line."\r\n");
        }

        return $this;
    }

    public function writeError($error) {
        foreach($this->_channels as $channel) {
            $channel->writeError($error);
        }

        foreach($this->_chunkReceivers as $receiver) {
            $receiver->writeChunk($error);
        }

        return $this;
    }

    public function writeErrorLine($line) {
        foreach($this->_channels as $channel) {
            $channel->writeErrorLine($line);
        }

        foreach($this->_chunkReceivers as $receiver) {
            $receiver->writeChunk($line."\r\n");
        }

        return $this;
    }




    public function readLine() {
        foreach($this->_channels as $channel) {
            if($channel instanceof core\io\IMultiplexReaderChannel) {
                return $channel->readLine();
            }
        }

        throw new RuntimeException(
            'There are no multiplex reader channels available'
        );
    }

    public function readChunk($size) {
        foreach($this->_channels as $channel) {
            if($channel instanceof core\io\IMultiplexReaderChannel) {
                if(false !== ($data = $channel->readChunk($size))) {
                    return $data;
                }
            }
        }

        return false;
    }

    public function setReadBlocking($flag) {
        foreach($this->_channels as $channel) {
            if($channel instanceof core\io\IMultiplexReaderChannel) {
                $channel->setReadBlocking($flag);
            }
        }

        return $this;
    }


// Dump
    public function getDumpProperties() {
        return $this->_channels;
    }
}