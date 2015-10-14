<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\csv;

use df;
use df\core;
use df\flex;

class Reader implements IReader {

    const BUFFER_READ_SIZE = 1024;
    const BUFFER_THRESHOLD = 256;

    const MODE_START = 0;
    const MODE_CELL = 1;
    const MODE_ENCLOSURE = 2;
    const MODE_CELL_AFTER_ENCLOSURE = 3;

    protected $_channel;
    protected $_delimiter = ',';
    protected $_enclosure = '"';
    protected $_fields = null;
    protected $_currentRow = null;
    protected $_rowCount = 0;
    protected $_buffer;
    protected $_rewindSeek = 0;

    public static function openFile($path) {
        ini_set('auto_detect_line_endings', true);
        return new self((new core\fs\File($path, core\fs\Mode::READ_ONLY))->setContentType('text/csv'));
    }

    public static function openString($string) {
        return new self(new core\fs\MemoryFile($string, 'text/csv', core\fs\Mode::READ_ONLY));
    }

    public function __construct(core\io\IChannel $channel) {
        $this->_channel = $channel;
        $this->_channel->seek(0);
    }

    public function getChannel() {
        return $this->_channel;
    }

// Chars
    public function setDelimiter($delimiter) {
        $this->_delimiter = (string)$delimiter;
        return $this;
    }

    public function getDelimiter() {
        return $this->_delimiter;
    }

    public function setEnclosure($enclosure) {
        $this->_enclosure = (string)$enclosure;
        return $this;
    }

    public function getEnclosure() {
        return $this->_enclosure;
    }


// Fields
    public function setFields($field1) {
        $fields = core\collection\Util::flattenArray(func_get_args());

        if(empty($fields)) {
            $fields = null;
        }

        $this->_fields = $fields;
        return $this;
    }

    public function extractFields() {
        if($this->_fields !== null) {
            throw new RuntimeException(
                'Fields have already been set'
            );
        }

        $this->setFields(array_values($this->getRow()));
        $this->_currentRow = null;
        $this->_rowCount = 0;
        $this->_rewindSeek = $this->_channel->tell() - strlen($this->_buffer);
        $this->_buffer = null;

        return $this;
    }

    public function getFields() {
        return $this->_fields;
    }

// Access
    public function getRow() {
        if($this->_currentRow === null) {
            $this->_readRow();
        }

        return $this->_currentRow;
    }

    public function toArray() {
        $output = [];

        foreach($this as $row) {
            $output[] = $row;
        }

        return $output;
    }

    protected function _readRow() {
        $this->_currentRow = [];
        $mode = self::MODE_START;
        $cell = '';

        while(true) {
            $isEof = !$this->_fillBuffer();
            $char = $this->_extract();

            if($char === null || $char === false || $char === '') {
                break;
            }

            switch($mode) {
                case self::MODE_START:
                    $cellHasEnclosure = false;

                    if($char == $this->_delimiter) {
                        $this->_writeCell($cell);
                    } else if($char == $this->_enclosure) {
                        $mode = self::MODE_ENCLOSURE;
                    } else if($char == "\r" || $char == "\n") {
                        if($char == "\r" && $this->_peek() == "\n") {
                            $this->_extract();
                        }

                        $this->_writeCell($cell);
                        break 2;
                    } else {
                        $cell .= $char;
                        $mode = self::MODE_CELL;
                    }

                    break;

                case self::MODE_CELL:
                    if($char == $this->_delimiter) {
                        $this->_writeCell($cell);
                        $mode = self::MODE_START;
                    } else if($char == $this->_enclosure && trim($cell) == '') {
                        $mode = self::MODE_ENCLOSURE;
                    } else if($cellHasEnclosure && $char == ' ') {
                        break;
                    } else if($char == "\r" || $char == "\n") {
                        if($char == "\r" && $this->_peek() == "\n") {
                            $this->_extract();
                        }

                        $this->_writeCell($cell);
                        break 2;
                    } else {
                        $cell .= $char;
                    }

                    break;

                case self::MODE_ENCLOSURE:
                    $cellHasEnclosure = true;

                    if($char == $this->_enclosure) {
                        if($this->_peek() == $this->_enclosure) {
                            $cell .= $this->_extract();
                        } else {
                            $mode = self::MODE_CELL;
                        }
                    } else {
                        $cell .= $char;
                    }

                    break;
            }
        }

        if(empty($this->_currentRow)) {
            $this->_currentRow = null;
        } else if(!empty($this->_fields)) {
            foreach($this->_fields as $field) {
                if(!array_key_exists($field, $this->_currentRow)) {
                    $this->_currentRow[$field] = null;
                }
            }
        }

        return $this->_currentRow;
    }

    protected function _peek($length=1) {
        return substr($this->_buffer, 0, $length);
    }

    protected function _extract($length=1) {
        $output = substr($this->_buffer, 0, $length);
        $this->_buffer = substr($this->_buffer, $length);
        return $output;
    }

    protected function _fillBuffer() {
        if(strlen($this->_buffer) > self::BUFFER_THRESHOLD) {
            return true;
        }

        if($this->_channel->eof()) {
            if(strlen($this->_buffer) && substr($this->_buffer, -1) != "\n") {
                $this->_buffer .= "\n";
            }

            return false;
        }

        $this->_buffer .= $this->_channel->readChunk(self::BUFFER_READ_SIZE);
        return $this->_buffer;
    }

    protected function _writeCell(&$cell) {
        $key = count($this->_currentRow);

        if($this->_fields !== null && isset($this->_fields[$key])) {
            $key = $this->_fields[$key];
        }

        $this->_currentRow[$key] = $cell;
        $cell = '';
    }

// Iterator
    public function rewind() {
        $this->_channel->seek($this->_rewindSeek);
        $this->_rowCount = 0;
        return $this;
    }

    public function current() {
        return $this->getRow();
    }

    public function key() {
        return $this->_rowCount;
    }

    public function next() {
        return $this->_readRow();
    }

    public function valid() {
        return !$this->_channel->eof() || strlen($this->_buffer) || $this->_currentRow !== null;
    }
}