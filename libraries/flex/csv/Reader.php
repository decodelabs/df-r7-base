<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex\csv;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\File;
use DecodeLabs\Atlas\Mode;

use DecodeLabs\Exceptional;

class Reader implements IReader
{
    public const BUFFER_READ_SIZE = 1024;
    public const BUFFER_THRESHOLD = 256;

    public const MODE_START = 0;
    public const MODE_CELL = 1;
    public const MODE_ENCLOSURE = 2;
    public const MODE_CELL_AFTER_ENCLOSURE = 3;

    protected $_file;
    protected $_delimiter = ',';
    protected $_enclosure = '"';
    protected $_fields = null;
    protected $_currentRow = null;
    protected $_rowCount = 0;
    protected $_buffer;
    protected $_rewindSeek = 0;

    public static function openFile($path): Reader
    {
        /** @phpstan-ignore-next-line */
        ini_set('auto_detect_line_endings', true);
        return new self(Atlas::file($path, Mode::READ_ONLY));
    }

    public static function openString(string $string): Reader
    {
        return new self(Atlas::createMemoryFile($string));
    }

    public function __construct(File $channel)
    {
        $this->_file = $channel;
        $this->_file->setPosition(0);
    }

    public function getFile(): File
    {
        return $this->_file;
    }

    // Chars
    public function setDelimiter(string $delimiter): IReader
    {
        $this->_delimiter = $delimiter;
        return $this;
    }

    public function getDelimiter(): string
    {
        return $this->_delimiter;
    }

    public function setEnclosure(string $enclosure): IReader
    {
        $this->_enclosure = $enclosure;
        return $this;
    }

    public function getEnclosure(): string
    {
        return $this->_enclosure;
    }


    // Fields
    public function setFields(string ...$fields): IReader
    {
        if (empty($fields)) {
            $fields = null;
        }

        $this->_fields = $fields;
        return $this;
    }

    public function extractFields(): IReader
    {
        if ($this->_fields !== null) {
            throw Exceptional::Runtime(
                'Fields have already been set'
            );
        }

        if (!$row = $this->getRow()) {
            throw Exceptional::UnexpectedValue(
                'Unable to extract fields row from CSV',
                null,
                $this
            );
        }

        $this->setFields(...$row);
        $this->_currentRow = null;
        $this->_rowCount = 0;
        $this->_rewindSeek = $this->_file->getPosition() - strlen((string)$this->_buffer);
        $this->_buffer = null;

        return $this;
    }

    public function getFields(): ?array
    {
        return $this->_fields;
    }

    // Access
    public function getRow(): ?array
    {
        if ($this->_currentRow === null) {
            $this->_readRow();
        }

        return $this->_currentRow;
    }

    public function toArray(): array
    {
        $output = [];

        foreach ($this as $row) {
            $output[] = $row;
        }

        return $output;
    }

    protected function _readRow()
    {
        $this->_currentRow = [];
        $mode = self::MODE_START;
        $cell = '';
        $cellHasEnclosure = false;

        while (true) {
            $isEof = !$this->_fillBuffer();
            $char = $this->_extract();

            if ($char === null || $char === '') {
                break;
            }

            switch ($mode) {
                case self::MODE_START:
                    $cellHasEnclosure = false;

                    if ($char == $this->_delimiter) {
                        $this->_writeCell($cell);
                    } elseif ($char == $this->_enclosure) {
                        $mode = self::MODE_ENCLOSURE;
                    } elseif ($char == "\r" || $char == "\n") {
                        if ($char == "\r" && $this->_peek() == "\n") {
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
                    if ($char == $this->_delimiter) {
                        $this->_writeCell($cell);
                        $mode = self::MODE_START;
                    } elseif ($char == $this->_enclosure && trim((string)$cell) == '') {
                        $mode = self::MODE_ENCLOSURE;
                    } elseif ($cellHasEnclosure && $char == ' ') {
                        break;
                    } elseif ($char == "\r" || $char == "\n") {
                        if ($char == "\r" && $this->_peek() == "\n") {
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

                    if ($char == $this->_enclosure) {
                        if ($this->_peek() == $this->_enclosure) {
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

        if (empty($this->_currentRow)) {
            $this->_currentRow = null;
        } elseif (!empty($this->_fields)) {
            foreach ($this->_fields as $field) {
                if (!array_key_exists($field, $this->_currentRow)) {
                    $this->_currentRow[$field] = null;
                }
            }
        }

        return $this->_currentRow;
    }

    protected function _peek(int $length = 1): ?string
    {
        return substr($this->_buffer, 0, $length);
    }

    protected function _extract(int $length = 1): ?string
    {
        $output = substr($this->_buffer, 0, $length);
        $this->_buffer = substr($this->_buffer, $length);
        return $output;
    }

    protected function _fillBuffer(): bool
    {
        if (strlen((string)$this->_buffer) > self::BUFFER_THRESHOLD) {
            return true;
        }

        if ($this->_file->isAtEnd()) {
            if (strlen((string)$this->_buffer) && substr($this->_buffer, -1) != "\n") {
                $this->_buffer .= "\n";
            }

            return false;
        }

        $this->_buffer .= $this->_file->read(self::BUFFER_READ_SIZE);
        return !empty($this->_buffer);
    }

    protected function _writeCell(?string &$cell): void
    {
        $key = count($this->_currentRow);

        if ($this->_fields !== null && isset($this->_fields[$key])) {
            $key = $this->_fields[$key];
        }

        $this->_currentRow[$key] = $cell;
        $cell = '';
    }

    // Iterator
    public function rewind(): void
    {
        $this->_file->setPosition($this->_rewindSeek);
        $this->_rowCount = 0;
    }

    public function current(): ?array
    {
        return $this->getRow();
    }

    public function key(): int
    {
        return $this->_rowCount;
    }

    public function next(): void
    {
        $this->_readRow();
    }

    public function valid(): bool
    {
        return
            !$this->_file->isAtEnd() ||
            strlen((string)$this->_buffer) ||
            $this->_currentRow !== null;
    }
}
