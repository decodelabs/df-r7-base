<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class BatchInsert implements IBatchInsertQuery, core\IDumpable {

    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Locational;
    use TQuery_DataInsert;
    use TQuery_Write;

    protected $_rows = [];
    protected $_preparedRows = [];
    protected $_fields = [];
    protected $_dereferencedFields = null;
    protected $_flushThreshold = 50;
    protected $_inserted = 0;

    public function __construct(ISourceManager $sourceManager, ISource $source, $rows, $shouldReplace=false) {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
        $this->_shouldReplace = (bool)$shouldReplace;

        $this->addRows($rows);
    }

    public function getQueryType() {
        if($this->_shouldReplace) {
            return IQueryTypes::BATCH_REPLACE;
        } else {
            return IQueryTypes::BATCH_INSERT;
        }
    }

    public function addRows($rows) {
        if($rows instanceof core\IArrayProvider) {
            $rows = $rows->toArray();
        } else if(!is_array($rows)) {
            throw new InvalidArgumentException(
                'Batch insert data must be convertible to an array'
            );
        }

        foreach($rows as $row) {
            $this->addRow($row);
        }

        return $this;
    }

    public function addRow($row) {
        $row = $this->_normalizeRow($row);

        foreach($row as $field => $value) {
            $this->_fields[$field] = true;
        }

        $this->_rows[] = $row;
        $this->_preparedRows = null;

        if($this->_flushThreshold > 0
        && count($this->_rows) >= $this->_flushThreshold) {
            $this->execute();
        }

        return $this;
    }

    protected function _normalizeRow($row) {
        if($row instanceof IDataRowProvider) {
            $row = $row->toDataRowArray();
        } else if($row instanceof core\IArrayProvider) {
            $row = $row->toArray();
        } else if(!is_array($row)) {
            throw new InvalidArgumentException(
                'Insert data must be convertible to an array'
            );
        }

        if(empty($row)) {
            throw new InvalidArgumentException(
                'Insert data must contain at least one field'
            );
        }

        return $row;
    }


    public function getRows() {
        return $this->_rows;
    }

    public function getPreparedRows() {
        if(!$this->_preparedRows) {
            $fields = [];
            $this->_preparedRows = $this->_deflateBatchInsertValues($this->_rows, $fields);

            if(!empty($fields)) {
                $this->_dereferencedFields = array_fill_keys($fields, true);
            }
        }

        return $this->_preparedRows;
    }

    public function clearRows() {
        $this->_rows = [];
        $this->_preparedRows = null;
        return $this;
    }

    public function getFields() {
        return array_keys($this->_fields);
    }

    public function getDereferencedFields() {
        $this->getPreparedRows();

        if($this->_dereferencedFields === null) {
            return $this->getFields();
        }

        return array_keys($this->_dereferencedFields);
    }


// Count
    public function countPending() {
        return count($this->_rows);
    }

    public function countInserted() {
        return $this->_inserted;
    }

    public function countTotal() {
        return $this->countPending() + $this->countInserted();
    }

// Flush threshold
    public function setFlushThreshold($flush) {
        $this->_flushThreshold = (int)$flush;
        return $this;
    }

    public function getFlushThreshold() {
        return $this->_flushThreshold;
    }

// Execute
    public function execute() {
        if(!empty($this->_rows)) {
            $this->_inserted += $this->_sourceManager->executeQuery($this, function($adapter) {
                return (int)$adapter->executeBatchInsertQuery($this);
            });
        }

        $this->clearRows();
        return $this->_inserted;
    }

    protected function _deflateBatchInsertValues(array $rows, array &$queryFields) {
        $adapter = $this->_source->getAdapter();

        if(!$adapter instanceof IIntegralAdapter) {
            return $rows;
        }

        $schema = $adapter->getQueryAdapterSchema();
        $fields = $schema->getFields();
        $queryFields = [];
        $values = [];

        foreach($rows as $row) {
            $rowValues = [];

            foreach($fields as $name => $field) {
                if($field instanceof opal\schema\INullPrimitiveField) {
                    continue;
                }

                if(!isset($row[$name])) {
                    $value = $field->generateInsertValue($row);
                } else {
                    $value = $field->sanitizeValue($row[$name]);
                }

                if($field instanceof opal\schema\IAutoTimestampField
                && ($value === null || $value === '')
                && $field->shouldTimestampAsDefault()) {
                    continue;
                }

                $value = $field->deflateValue($value);

                if(is_array($value)) {
                    foreach($value as $key => $val) {
                        $rowValues[$key] = $val;
                        $queryFields[$key] = true;
                    }
                } else {
                    $rowValues[$name] = $value;
                    $queryFields[$name] = true;
                }
            }

            $values[] = $rowValues;
        }

        $queryFields = array_keys($queryFields);
        return $values;
    }


// Dump
    public function getDumpProperties() {
        return [
            'source' => $this->_source->getAdapter(),
            'fields' => implode(', ', array_keys($this->_fields)),
            'pending' => count($this->_rows),
            'inserted' => $this->_inserted,
            'flushThreshold' => $this->_flushThreshold
        ];
    }
}
