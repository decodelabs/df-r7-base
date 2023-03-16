<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex\csv;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\Mode;
use DecodeLabs\Deliverance\DataReceiver;
use DecodeLabs\Exceptional;

class Builder implements IBuilder
{
    protected $_fields = null;
    protected $_rows = [];
    protected $_writeFields = true;
    protected $_fieldsWritten = false;

    protected $_receiver;
    protected $_generator;

    public static function openFile($path, ?callable $generator = null)
    {
        return (new self($generator))
            ->setDataReceiver(
                Atlas::file($path, Mode::READ_WRITE_TRUNCATE)
            );
    }

    public static function openString(?callable $generator = null)
    {
        return (new self($generator))
            ->setDataReceiver(
                Atlas::newMemoryFile()
            );
    }

    public function __construct(?callable $generator = null)
    {
        $this->setGenerator($generator);
    }

    public function setDataReceiver(DataReceiver $receiver): static
    {
        $this->_receiver = $receiver;
        return $this;
    }

    public function getDataReceiver(): ?DataReceiver
    {
        return $this->_receiver;
    }

    public function setGenerator(?callable $generator): IBuilder
    {
        $this->_generator = $generator;
        return $this;
    }

    public function getGenerator(): ?callable
    {
        return $this->_generator;
    }

    public function build(): DataReceiver
    {
        $this->sendData();
        return $this->_receiver;
    }

    public function sendData(): void
    {
        if ($this->_generator) {
            ($this->_generator)($this);

            if ($this->_writeFields && !$this->_fieldsWritten) {
                $this->_writeRow($this->_fields);
                $this->_fieldsWritten = true;
            }
        } elseif (!empty($this->_fields)) {
            if ($this->_writeFields && !$this->_fieldsWritten) {
                $this->_writeRow($this->_fields);
                $this->_fieldsWritten = true;
            }

            if (!empty($this->_rows)) {
                foreach ($this->_rows as $row) {
                    $this->_writeRow($row);
                }
            }
        } else {
            throw Exceptional::Runtime(
                'No data has been generated for CSV builder'
            );
        }
    }


    public function setFields(array $fields): IBuilder
    {
        if (empty($fields)) {
            throw Exceptional::Runtime(
                'CSV file must have at least one field'
            );
        }

        $this->_fields = $fields;
        return $this;
    }

    public function getFields(): ?array
    {
        return $this->_fields;
    }

    public function shouldWriteFields(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_writeFields = $flag;
            return $this;
        }

        return $this->_writeFields;
    }

    public function addInfoRow(array $row): void
    {
        if (empty($this->_fields)) {
            throw Exceptional::Runtime('Fields must be set before writing info rows');
        }

        if ($this->_fieldsWritten) {
            throw Exceptional::Runtime('Info rows must be written before data rows');
        }

        $newRow = [];

        foreach ($this->_fields as $key => $label) {
            $newRow[] = array_shift($row) ?? null;
        }

        $this->_writeRow($newRow);
    }

    public function addRow(array $row): void
    {
        if (empty($this->_fields)) {
            $fields = [];

            foreach ($row as $key => $value) {
                $fields[$key] = $key;
            }

            $this->setFields($fields);
        }

        if ($this->_writeFields && !$this->_fieldsWritten) {
            if ($this->_generator) {
                $this->_writeRow($this->_fields);
            } else {
                $this->_rows[] = $this->_fields;
            }

            $this->_fieldsWritten = true;
        }

        $outRow = [];

        foreach ($this->_fields as $key => $label) {
            if (isset($row[$key])) {
                $value = $row[$key];
            } else {
                $value = null;
            }

            if (is_bool($value)) {
                $value = $value ? 'Yes' : 'No';
            }

            $outRow[$key] = $value;
        }

        if ($this->_generator) {
            $this->_writeRow($outRow);
        } else {
            $this->_rows[] = $outRow;
        }
    }

    public function getRows(): array
    {
        return $this->_rows;
    }

    protected function _writeRow(array $row): void
    {
        if (!$this->_receiver) {
            throw Exceptional::Runtime(
                'No data receiver has been set for CSV builder'
            );
        }

        $this->_receiver->write($this->_writeCsv($row));
    }

    protected function _writeCsv(array $data = [], string $delimiter = ',', string $enclosure = '"'): string
    {
        $str = '';
        $escape_char = '\\';

        foreach ($data as $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            } else {
                $value = (string)$value;
            }

            if (strpos($value, $delimiter) !== false ||
                strpos($value, $enclosure) !== false ||
                strpos($value, "\n") !== false ||
                strpos($value, "\r") !== false ||
                strpos($value, "\t") !== false ||
                strpos($value, ' ') !== false) {
                $str2 = $enclosure;
                $escaped = 0;
                $len = strlen($value);

                for ($i = 0; $i < $len; $i++) {
                    if ($value[$i] == $escape_char) {
                        $escaped = 1;
                    } elseif (!$escaped && $value[$i] == $enclosure) {
                        $str2 .= $enclosure;
                    } else {
                        $escaped = 0;
                    }

                    $str2 .= $value[$i];
                }

                $str2 .= $enclosure;
                $str .= $str2 . $delimiter;
            } else {
                $str .= $value . $delimiter;
            }
        }

        $str = substr($str, 0, -1);
        $str .= "\n";

        return $str;
    }
}
