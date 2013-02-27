<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\halo\task;

use df;
use df\core;
use df\halo;
    
class Response implements IResponse, core\IDumpable {

    const REGISTRY_KEY = 'taskResponse';

    protected $_channels = array();

    public static function defaultFactory() {
        if(isset($_SERVER['argv']) && !df\Launchpad::$invokingApplication) {
            $channel = new core\io\channel\Std();
        } else {
            $channel = new core\io\channel\Memory();
        }

        return new self([$channel]);
    }

    public function __construct(array $channels=null) {
        if($channels !== null) {
            $this->addChannels($channels);
        }
    }


// Registry
    public function getRegistryObjectKey() {
        return static::REGISTRY_KEY;
    }

    public function onApplicationShutdown() {}


// Channels
    public function setChannels(array $channels) {
        $this->_channels = array();
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
        $this->_channels = array();
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

        return $this;
    }

    public function writeLine($line) {
        foreach($this->_channels as $channel) {
            $channel->writeLine($line);
        }

        return $this;
    }

    public function writeError($error) {
        foreach($this->_channels as $channel) {
            $channel->writeError($error);
        }

        return $this;
    }

    public function writeErrorLine($line) {
        foreach($this->_channels as $channel) {
            $channel->writeErrorLine($line);
        }

        return $this;
    }

// Dump
    public function getDumpProperties() {
        return $this->_channels;
    }
}