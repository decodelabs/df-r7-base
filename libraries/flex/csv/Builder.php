<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\csv;

use df;
use df\core;
use df\flex;
use df\mesh;
    
class Builder implements IBuilder {

    protected $_fields = null;
    protected $_rows = null;
    protected $_writeFields = true;

    protected $_receiver;
    protected $_generator;

    public static function openFile($path, $generator=null) {
        return (new self($generator))
            ->setChunkReceiver(
                (new core\io\channel\File($path, core\io\IMode::READ_WRITE_TRUNCATE))
                    ->setContentType('text/csv')
            );
    }

    public static function openString($generator=null) {
        return (new self($generator))
            ->setChunkReceiver(
                new core\io\channel\Memory(null, 'text/csv', core\io\IMode::READ_WRITE_TRUNCATE)
            );
    }

    public function __construct($generator=null) {
        $this->setGenerator($generator);
    }

    public function setChunkReceiver(core\io\IChunkReceiver $receiver) {
        $this->_receiver = $receiver;
        return $this;
    }

    public function getChunkReceiver() {
        return $this->_receiver;
    }

    public function setGenerator($generator=null) {
        if($generator !== null) {
            $generator = mesh\Callback::factory($generator);
        }

        $this->_generator = $generator;
        return $this;
    }

    public function getGenerator() {
        return $this->_generator;
    }

    public function sendChunks() {
        if($this->_generator) {
            $this->_generator->invokeArgs([$this]);
        } else if(!empty($this->_fields)) {
            if($this->_writeFields) {
                $this->_writeRow($this->_fields);
            }

            if(!empty($this->_rows)) {
                foreach($this->_rows as $row) {
                    $this->_writeRow($row);
                }
            }
        } else {
            throw new RuntimeException(
                'No data has been generated for CSV builder'
            );
        }

        return $this->_receiver;
    }


    public function setFields(array $fields) {
        if(empty($fields)) {
            throw new RuntimeException('CSV file must have at least one field');
        }

        $this->_fields = $fields;

        if($this->_generator && $this->_writeFields) {
            $this->_writeRow($this->_fields);
        }

        return $this;
    }

    public function getFields() {
        return $this->_fields;
    }

    public function shouldWriteFields($flag=null) {
        if($flag !== null) {
            $this->_writeFields = (bool)$flag;
            return $this;
        }

        return $this->_writeFields;
    }

    public function addRow(array $row) {
        if(empty($this->_fields)) {
            $fields = [];

            foreach($row as $key => $value) {
                $fields[$key] = $key;
            }

            $this->setFields($fields);
        }

        $outRow = [];

        foreach($this->_fields as $key => $label) {
            if(isset($row[$key])) {
                $outRow[$key] = $row[$key];
            } else {
                $outRow[$key] = null;
            }
        }

        if($this->_generator) {
            $this->_writeRow($outRow);
        } else {
            $this->_rows[] = $outRow;
        }
    }

    public function getRows() {
        return $this->_rows;
    }

    protected function _writeRow(array $row) {
        if(!$this->_receiver) {
            throw new RuntimeException(
                'No data receiver has been set for CSV builder'
            );
        }

        $this->_receiver->writeChunk($this->_writeCsv($row));
    }

    protected function _writeCsv($data=[], $delimiter=',', $enclosure='"') {
        $str = '';
        $escape_char = '\\';

        foreach($data as $value) {
            if(is_array($value)) {
                $value = implode(',', $value);
            } else if(!is_scalar($value)) {
                $value = (string)$value;
            }

            if(strpos($value, $delimiter) !== false ||
               strpos($value, $enclosure) !== false ||
               strpos($value, "\n") !== false ||
               strpos($value, "\r") !== false ||
               strpos($value, "\t") !== false ||
               strpos($value, ' ') !== false) {
                $str2 = $enclosure;
                $escaped = 0;
                $len = strlen($value);

                for($i = 0; $i < $len; $i++) {
                    if($value[$i] == $escape_char) {
                        $escaped = 1;
                    } else if (!$escaped && $value[$i] == $enclosure) {
                        $str2 .= $enclosure;
                    } else {
                        $escaped = 0;
                    }

                    $str2 .= $value[$i];
                }

                $str2 .= $enclosure;
                $str .= $str2.$delimiter;
            } else {
                $str .= $value.$delimiter;
            }
        }

        $str = substr($str,0,-1);
        $str .= "\n";

        return $str;
    }
}