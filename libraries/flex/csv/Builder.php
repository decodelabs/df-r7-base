<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\csv;

use df;
use df\core;
use df\flex;
    
class Builder implements IBuilder {

    protected $_fields = null;
    protected $_rows = null;
    protected $_writeFields = true;

    protected $_receiver;
    protected $_generator;

    public function __construct(Callable $generator=null) {
        $this->setGenerator($generator);
    }

    public function setChunkReceiver(core\io\IChunkReceiver $receiver) {
        $this->_receiver = $receiver;
        return $this;
    }

    public function getChunkReceiver() {
        return $this->_receiver;
    }

    public function setGenerator(Callable $generator=null) {
        $this->_generator = $generator;
        return $this;
    }

    public function getGenerator() {
        return $this->_generator;
    }

    public function sendChunks() {
        if($this->_generator) {
            $this->_generator->__invoke($this);
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

        return $this;
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

        $this->_receiver->writeChunk(
            core\string\Util::implodeDelimited($row)."\r\n"
        );
    }
}