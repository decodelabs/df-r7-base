<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\link\http\response;

use df;
use df\core;
use df\link;

use DecodeLabs\Atlas\DataReceiver;
use DecodeLabs\Atlas\DataReceiverTrait;
use DecodeLabs\Atlas\DataSender;
use DecodeLabs\Exceptional;

class Generator extends Base implements link\http\IGeneratorResponse
{
    use DataReceiverTrait;

    protected $_sender;
    protected $_channel;

    protected $_writeCallback;

    public function __construct($contentType, callable $sender, link\http\IResponseHeaderCollection $headers=null)
    {
        parent::__construct($headers);
        $this->_sender = $sender;

        $this->headers
            ->set('content-type', $contentType)
            //->set('transfer-encoding', 'chunked')
            ->setCacheAccess('no-cache')
            ->getCacheControl()
                ->canStore(false)
            ;
    }

    public function setWriteCallback(callable $callback)
    {
        $this->_writeCallback = $callback;
        return $this;
    }

    public function generate(DataReceiver $stream)
    {
        $this->_channel = $stream;

        while (ob_get_level()) {
            ob_end_clean();
        }

        flush();
        ob_implicit_flush(1);

        $gen = ($this->_sender)($this);

        if ($gen instanceof \Generator) {
            foreach ($gen as $chunk) {
                $this->write($chunk);
            }
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
        if ($this->_writeCallback) {
            ($this->_writeCallback)($this);
            $this->_writeCallback = null;
        }

        if ($data === null) {
            return 0;
        }

        if ($length !== null) {
            $data = substr($data, 0, $length);
        }

        if ($this->_channel) {
            $this->_channel->write($data);
        }

        return strlen($data);
    }

    public function writeBrowserKeepAlive()
    {
        return $this->write(str_repeat(' ', 1024));
    }

    public function getContent()
    {
        throw Exceptional::Runtime(
            'Streamed generator responses can only generate their content via a channel'
        );
    }
}
