<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\response;

use df;
use df\core;
use df\link;

use DecodeLabs\Glitch;
use DecodeLabs\Atlas\DataReceiverTrait;
use DecodeLabs\Atlas\DataSender;

class Generator extends Base implements link\http\IGeneratorResponse
{
    use DataReceiverTrait;

    protected $_sender;
    protected $_channel;
    protected $_manualChunk = false;

    public function __construct($contentType, $sender, link\http\IResponseHeaderCollection $headers=null)
    {
        parent::__construct($headers);
        $this->_sender = $sender;

        if ($this->_sender instanceof DataSender) {
            $this->_sender->setDataReceiver($this);
        } elseif (!is_callable($this->_sender)) {
            throw Glitch::ERuntime(
                'Generator sender must either be a Atlas\\DataSender or callable'
            );
        }

        $this->headers
            ->set('content-type', $contentType)
            //->set('transfer-encoding', 'chunked')
            ->setCacheAccess('no-cache')
            ->getCacheControl()
                ->canStore(false)
            ;
    }

    public function shouldChunkManually(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_manualChunk = $flag;
            return $this;
        }

        return $this->_manualChunk;
    }

    public function generate(core\io\IChannel $channel)
    {
        $this->_channel = $channel;

        if ($this->_sender instanceof DataSender) {
            $this->_sender->sendData();
        } else {
            $gen = $this->_sender->__invoke($this);

            if ($gen instanceof \Generator) {
                foreach ($gen as $chunk) {
                    $this->write($chunk);
                }
            }
        }

        if ($this->_manualChunk) {
            $this->_channel->write("0\r\n\r\n");
        }

        $this->_channel = null;

        return $this;
    }

    public function getChannel()
    {
        return $this->_channel;
    }

    public function write(?string $data, int $length=null): int
    {
        if ($data === null) {
            return 0;
        }

        if ($length !== null) {
            $data = substr($data, 0, $length);
        }

        if ($this->_channel) {
            if ($this->_manualChunk) {
                $this->_channel->write(dechex(strlen($data))."\r\n");
                $this->_channel->write($data."\r\n");
            } else {
                $this->_channel->write($data);
            }
        }

        return strlen($data);
    }

    public function writeBrowserKeepAlive()
    {
        return $this->write(str_repeat(' ', 1024));
    }

    public function getContent()
    {
        throw Glitch::ERuntime(
            'Streamed generator responses can only generate their content via a channel'
        );
    }
}
