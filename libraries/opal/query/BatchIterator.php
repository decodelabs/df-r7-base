<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class BatchIterator implements IBatchIterator {

    use core\collection\TExtractList;

    const DEFAULT_BATCH_SIZE = 50;

    protected $_isForFetch = false;
    protected $_populates = [];
    protected $_attachments = [];
    protected $_combines = [];

    protected $_keyField;
    protected $_valField;
    protected $_formatter;
    protected $_nestFields = [];

    protected $_batchData = [];
    protected $_batchSize = self::DEFAULT_BATCH_SIZE;
    protected $_batchNumber = 0;
    protected $_result;
    protected $_arrayManipulator;

    protected $_currentKey = null;

    public function __construct(ISource $source, $result, IOutputManifest $outputManifest=null) {
        $this->_batchSize = static::DEFAULT_BATCH_SIZE;
        $this->_arrayManipulator = new opal\native\ArrayManipulator($source, [], true, $outputManifest);

        if(is_array($result)) {
            $result = new core\collection\Queue(...$result);
        }

        if(!$result instanceof core\collection\ICollection) {
            throw new InvalidArgumentException(
                'Query result is not an ICollection - maybe you need to use a custom batch iterator?'
            );
        }

        $this->_result = $result;
    }


    public function getResult() {
        return $this->_result;
    }

    public function isForFetch(bool $flag=null) {
        if($flag !== null) {
            $this->_isForFetch = $flag;
            return $this;
        }

        return $this->_isForFetch;
    }


    public function getPrimarySource() {
        return $this->_arrayManipulator->getSource();
    }

    public function addSources(array $sources) {
        $manifest = $this->_arrayManipulator->getOutputManifest();

        foreach($sources as $source) {
            $manifest->importSource($source);
        }

        return $this;
    }

    public function getSources() {
        return $this->_arrayManipulator->getOutputManifest()->getSources();
    }


    public function setPopulates(array $populates) {
        $this->_populates = $populates;
        return $this;
    }

    public function getPopulates() {
        return $this->_populates;
    }


    public function setAttachments(array $attachments) {
        $this->_attachments = $attachments;
        return $this;
    }

    public function getAttachments() {
        return $this->_attachments;
    }

    public function setCombines(array $combines) {
        $this->_combines = $combines;
        return $this;
    }

    public function getCombines() {
        return $this->_combines;
    }

    public function setListKeyField(IField $field=null) {
        $this->_keyField = $field;
        return $this;
    }

    public function getListKeyField() {
        return $this->_keyField;
    }

    public function setListValueField(IField $field=null) {
        $this->_valField = $field;
        return $this;
    }

    public function getListValueField() {
        return $this->_valField;
    }

    public function setFormatter(Callable $formatter=null) {
        $this->_formatter = $formatter;
        return $this;
    }

    public function getFormatter() {
        return $this->_formatter;
    }

    public function setNestFields(IField ...$fields) {
        $this->_nestFields = $fields;
        return $this;
    }

    public function getNestFields() {
        return $this->_nestFields;
    }




    public function setBatchSize($size) {
        $this->_batchSize = (int)$size;
        return $this;
    }

    public function getBatchSize() {
        return $this->_batchSize;
    }

    public function import(...$values) {
        throw new RuntimeException('This collection is read only');
    }

    public function isEmpty() {
        return empty($this->_batchData) && $this->_isResultEmpty();
    }

    protected function _isResultEmpty() {
        return $this->_result->isEmpty();
    }


    public function clear() {
        throw new RuntimeException('This collection is read only');
    }

    public function extract() {
        if(empty($this->_batchData)) {
            $this->_fetchBatch();

            if(empty($this->_batchData)) {
                return null;
            }
        }

        $this->_currentKey = key($this->_batchData);
        $output = $this->_batchData[$this->_currentKey];
        unset($this->_batchData[$this->_currentKey]);

        if($this->_currentKey === '') {
            $this->_currentKey = null;
        }

        return $output;
    }

    protected function _fetchBatch() {
        $batchSize = $this->_batchSize;

        if($batchSize <= 0) {
            $batchSize = self::DEFAULT_BATCH_SIZE;
        }

        $batch = [];
        $allInOne = !empty($this->_nestFields);

        while(!$this->_isResultEmpty() && $batchSize > 0) {
            $batch[] = $this->_extractResult();

            if(!$allInOne) {
                $batchSize--;
            }
        }

        $this->_arrayManipulator->setRows($batch);
        $this->_batchData = $this->_arrayManipulator->applyBatchIteratorExpansion($this, $this->_batchNumber++);
        reset($this->_batchData);
    }

    protected function _extractResult() {
        return $this->_result->extract();
    }


    public function count() {
        return count($this->_batchData) + $this->_countResult();
    }

    protected function _countResult() {
        return $this->_result->count();
    }

    public function toArray() {
        $output = [];
        $useKey = $this->_keyField || !empty($this->_nestFields);

        while(!$this->isEmpty()) {
            if($useKey) {
                if(empty($this->_batchData)) {
                    $this->_fetchBatch();
                }

                $key = key($this->_batchData);
                $output[$key] = $this->extract();
            } else {
                $output[] = $this->extract();
            }
        }

        return $output;
    }

// Iterator
    public function current() {
        return $this->extract();
    }

    public function next() {
        $this->_currentKey = null;
    }

    public function key() {
        return $this->_currentKey;
    }

    public function valid() {
        return !$this->isEmpty();
    }

    public function rewind() {

    }
}
