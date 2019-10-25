<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io;

use df;
use df\core;

use DecodeLabs\Systemic\Process\Launcher;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\Broker;
use DecodeLabs\Atlas\DataReceiver;
use DecodeLabs\Atlas\Channel\Stream;
use DecodeLabs\Atlas\DataReceiver\Proxy;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Multiplexer implements IMultiplexer, Inspectable
{
    const REGISTRY_KEY = 'multiplexer';

    protected $_id;
    protected $_channels = [];
    protected $_chunkReceivers = [];
    protected $_dataReceivers = [];
    protected $_newLine = true;

    public static function defaultFactory($id=null)
    {
        if (isset($_SERVER['argv']) && $id != 'memory') {
            $channels = [new core\io\Std()];
        } else {
            $channels = [];
        }

        return new self($channels, $id);
    }

    public function __construct(array $ioSet=null, $id=null)
    {
        $this->setId($id);

        if ($ioSet !== null) {
            foreach ($ioSet as $id => $ioNode) {
                if ($ioNode instanceof IChannel) {
                    $this->addChannel($ioNode);
                } elseif ($ioNode instanceof IChunkReceiver) {
                    $this->addChunkReceiver($id, $ioNode);
                } elseif ($ioNode instanceof DataReceiver) {
                    $this->addDataReceiver($id, $ioNode);
                }
            }
        }
    }

    public function setId(?string $id)
    {
        $this->_id = $id;
        return $this;
    }

    public function getId(): ?string
    {
        return $this->_id;
    }



    // Registry
    public function getRegistryObjectKey(): string
    {
        $output = static::REGISTRY_KEY;

        if ($this->_id) {
            $output .= ':'.$this->_id;
        }

        return $output;
    }

    // Channels
    public function setChannels(array $channels)
    {
        $this->_channels = [];
        return $this->addChannels($channels);
    }

    public function addChannels(array $channels)
    {
        foreach ($channels as $channel) {
            if ($channel instanceof core\io\IChannel) {
                $this->addChannel($channel);
            }
        }

        return $this;
    }

    public function addChannel(core\io\IChannel $channel)
    {
        $this->_channels[$channel->getChannelId()] = $channel;
        return $this;
    }

    public function hasChannel($id)
    {
        if ($id instanceof core\io\IChannel) {
            $id = $id->getChannelId();
        }

        return isset($this->_channels[$id]);
    }

    public function getChannel($id)
    {
        if ($id instanceof core\io\IChannel) {
            $id = $id->getChannelId();
        }

        if (isset($this->_channels[$id])) {
            return $this->_channels[$id];
        }
    }

    public function getFirstChannel()
    {
        foreach ($this->_channels as $channel) {
            return $channel;
        }
    }

    public function removeChannel($id)
    {
        if ($id instanceof core\io\IChannel) {
            $id = $id->getChannelId();
        }

        unset($this->_channels[$id]);
        return $this;
    }

    public function getChannels()
    {
        return $this->_channels;
    }

    public function clearChannels()
    {
        $this->_channels = [];
        return $this;
    }


    // Chunk receivers
    public function setChunkReceivers(array $receivers)
    {
        $this->_chunkReceivers = [];
        return $this->addChunkReceivers($receivers);
    }

    public function addChunkReceivers(array $receivers)
    {
        foreach ($receivers as $id => $receiver) {
            if ($receiver instanceof IChunkReceiver) {
                $this->addChunkReceiver($id, $receiver);
            }
        }

        return $this;
    }

    public function addChunkReceiver($id, IChunkReceiver $receiver)
    {
        $this->_chunkReceivers[$id] = $receiver;
        return $this;
    }

    public function hasChunkReceiver($id)
    {
        return isset($this->_chunkReceivers[$id]);
    }

    public function getChunkReceiver($id)
    {
        if (isset($this->_chunkReceivers[$id])) {
            return $this->_chunkReceivers[$id];
        }
    }

    public function getChunkReceivers()
    {
        return $this->_chunkReceivers;
    }

    public function clearChunkReceivers()
    {
        $this->_chunkReceivers = [];
        return $this;
    }



    // Data receivers
    public function setDataReceivers(array $receivers)
    {
        $this->_dataReceivers = [];
        return $this->addDataReceivers($receivers);
    }

    public function addDataReceivers(array $receivers)
    {
        foreach ($receivers as $id => $receiver) {
            if ($receiver instanceof DataReceiver) {
                $this->addDataReceiver($id, $receiver);
            }
        }

        return $this;
    }

    public function addDataReceiver($id, DataReceiver $receiver)
    {
        $this->_dataReceivers[$id] = $receiver;
        return $this;
    }

    public function hasDataReceiver($id)
    {
        return isset($this->_dataReceivers[$id]);
    }

    public function getDataReceiver($id)
    {
        if (isset($this->_dataReceivers[$id])) {
            return $this->_dataReceivers[$id];
        }
    }

    public function getDataReceivers()
    {
        return $this->_dataReceivers;
    }

    public function clearDataReceivers()
    {
        $this->_dataReceivers = [];
        return $this;
    }


    // IO
    public function flush()
    {
        foreach ($this->_channels as $channel) {
            $channel->flush();
        }

        return $this;
    }

    public function write($data)
    {
        if (false !== strpos($data, "\n")) {
            $lines = explode("\n", $data);
            $data = array_pop($lines);

            foreach ($lines as $line) {
                $this->writeLine($line);
            }

            if (!strlen($data)) {
                return;
            }
        }

        if (strlen($data)) {
            $this->_newLine = false;
        }

        foreach ($this->_channels as $channel) {
            $channel->write($data);
        }

        foreach ($this->_chunkReceivers as $receiver) {
            $receiver->writeChunk($data);
        }

        foreach ($this->_dataReceivers as $receiver) {
            $receiver->write($data);
        }

        return $this;
    }

    public function writeLine($line='')
    {
        foreach ($this->_channels as $channel) {
            $channel->writeLine($line);
        }

        foreach ($this->_chunkReceivers as $receiver) {
            $receiver->writeChunk($line."\r\n");
        }

        foreach ($this->_dataReceivers as $receiver) {
            $receiver->writeLine($line);
        }

        $this->_newLine = true;
        return $this;
    }

    public function writeError($error)
    {
        if (false !== strpos($error, "\n")) {
            $lines = explode("\n", $error);
            $error = array_pop($lines);

            foreach ($lines as $line) {
                $this->writeErrorLine($line);
            }

            if (!strlen($error)) {
                return;
            }
        }

        if (strlen($error)) {
            $this->_newLine = false;
        }

        foreach ($this->_channels as $channel) {
            $channel->writeError($error);
        }

        foreach ($this->_chunkReceivers as $receiver) {
            $receiver->writeChunk($error);
        }

        foreach ($this->_dataReceivers as $receiver) {
            $receiver->write($error);
        }

        return $this;
    }

    public function writeErrorLine($line)
    {
        foreach ($this->_channels as $channel) {
            $channel->writeErrorLine($line);
        }

        foreach ($this->_chunkReceivers as $receiver) {
            $receiver->writeChunk($line."\r\n");
        }

        foreach ($this->_dataReceivers as $receiver) {
            $receiver->writeLine($line);
        }

        $this->_newLine = true;
        return $this;
    }


    public function readLine()
    {
        foreach ($this->_channels as $channel) {
            if ($channel instanceof core\io\IMultiplexReaderChannel) {
                return $channel->readLine();
            }
        }

        throw Glitch::ERuntime(
            'There are no multiplex reader channels available'
        );
    }

    public function readChunk($size)
    {
        foreach ($this->_channels as $channel) {
            if ($channel instanceof core\io\IMultiplexReaderChannel) {
                if (false !== ($data = $channel->readChunk($size))) {
                    return $data;
                }
            }
        }

        return false;
    }

    public function setReadBlocking($flag)
    {
        foreach ($this->_channels as $channel) {
            if ($channel instanceof core\io\IMultiplexReaderChannel) {
                $channel->setReadBlocking($flag);
            }
        }

        return $this;
    }


    public function exportToAtlasLauncher(Launcher $launcher): IMultiplexer
    {
        $broker = Atlas::newBroker();

        foreach ($this->getChannels() as $channel) {
            if ($channel instanceof IMultiplexReaderChannel) {
                $broker
                    ->addInputProvider(Atlas::openCliInputStream())
                    ->addOutputReceiver(Atlas::openCliOutputStream())
                    ->addErrorReceiver(Atlas::openCliErrorStream());
            } elseif ($channel instanceof IStreamChannel) {
                $stream = new Stream($channel->getStreamDescriptor());
                $broker->addOutputReceiver($stream);
                $broker->addErrorReceiver($stream);
            } else {
                $receiver = new Proxy($channel, function ($channel, $data) {
                    $channel->writeChunk($data);
                });

                $broker
                    ->addOutputReceiver($receiver)
                    ->addErrorReceiver($receiver);
            }
        }

        foreach ($this->getChunkReceivers() as $receiver) {
            $channel = new Proxy($receiver, function ($receiver, $data) {
                $receiver->writeChunk($data);
            });

            $broker
                ->addOutputReceiver($channel)
                ->addErrorReceiver($channel);
        }

        foreach ($this->getDataReceivers() as $receiver) {
            $broker
                ->addOutputReceiver($receiver)
                ->addErrorReceiver($receiver);
        }

        $launcher->setIoBroker($broker);
        return $this;
    }


    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity
            ->setValues($inspector->inspectList($this->_channels));
    }
}
