<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io;

use df;
use df\core;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Stream implements IStreamChannel, Inspectable
{
    use TReader;
    use TWriter;

    protected $_resource;
    protected $_error = '';
    protected $_id;

    public function __construct($resource, $id=null)
    {
        $blocking = false;

        if (is_string($resource)) {
            $resource = fopen($resource, 'a+');
            $blocking = true;
        }

        $this->_resource = $resource;

        if ($blocking) {
            $this->setBlocking(true);
        }

        if ($id === null) {
            $id = 'Stream:'.$this->_resource;
        }

        $this->_id = $id;
    }

    public function getChannelId()
    {
        return $this->_id;
    }

    public function flush()
    {
        return $this;
    }


    // Error
    public function getErrorBuffer()
    {
        return $this->_error;
    }

    public function flushErrorBuffer()
    {
        $output = $this->_error;
        $this->_error = null;

        return $output;
    }

    public function writeError($error)
    {
        $this->_error .= $error;
        return $this;
    }

    public function writeErrorLine($line)
    {
        return $this->writeError($line."\r\n");
    }


    protected function _readChunk($length)
    {
        try {
            $output = fread($this->_resource, $length);
        } catch (\Throwable $e) {
            return false;
        }

        if ($output === ''
        || $output === null
        || $output === false) {
            return false;
        }

        return $output;
    }

    protected function _readLine()
    {
        try {
            $output = fgets($this->_resource);
        } catch (\Throwable $e) {
            return false;
        }

        if ($output === ''
        || $output === null
        || $output === false) {
            return false;
        }

        return $output;
    }

    protected function _writeChunk($data, $length)
    {
        return fwrite($this->_resource, $data, $length);
    }


    // Stream
    public function getStreamDescriptor()
    {
        return $this->_resource;
    }

    public function getMetadata()
    {
        if (!$this->_resource) {
            throw new RuntimeException(
                'Stream is not live'
            );
        }

        return stream_get_meta_data($this->_resource);
    }

    public function setBlocking($flag)
    {
        stream_set_blocking($this->_resource, (int)($flag));
        return $this;
    }

    public function getBlocking()
    {
        if (!$this->_resource) {
            return false;
        }

        $meta = stream_get_meta_data($this->_resource);
        return (bool)$meta['blocked'];
    }

    public function close()
    {
        @fclose($this->_resource);
        return $this;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setSingleValue($inspector($this->_resource));
    }
}
