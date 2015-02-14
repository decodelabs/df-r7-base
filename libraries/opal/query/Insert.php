<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Insert implements IInsertQuery, core\IDumpable {
    
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Locational;
    use TQuery_DataInsert;

    protected $_row;

    public function __construct(ISourceManager $sourceManager, ISource $source, $row, $shouldReplace=false) {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
        $this->_shouldReplace = (bool)$shouldReplace;
        
        $this->setRow($row);
    }
    
    public function getQueryType() {
        if($this->_shouldReplace) {
            return IQueryTypes::REPLACE;
        } else {
            return IQueryTypes::INSERT;
        }
    }

    public function setRow($row) {
        if($this instanceof ILocationalQuery && $row instanceof opal\record\ILocationalRecord) {
            $this->inside($row->getQueryLocation());
        }

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
        
        $this->_row = $row;
        return $this;
    }
    
    public function getRow() {
        return $this->_row;
    }
    
    
    public function execute() {
        $this->_row = $this->_deflateInsertValues($this->_row);
        
        $output = $this->_sourceManager->executeQuery($this, function($adapter) {
            return $adapter->executeInsertQuery($this);
        });
        
        return $this->_normalizeInsertId($output, $this->_row);
    }

    protected function _normalizeInsertId($originalId, array $row) {
        $adapter = $this->_source->getAdapter();

        if(!$adapter instanceof IIntegralAdapter) {
            return $originalId;
        }

        $index = $adapter->getQueryAdapterSchema()->getPrimaryIndex();

        if(!$index) {
            return $originalId;
        }

        $fields = $index->getFields();
        $values = [];
        
        foreach($fields as $name => $field) {
            if($originalId 
            && (($field instanceof opal\schema\IAutoIncrementableField && $field->shouldAutoIncrement())
              || $field instanceof opal\schema\IAutoGeneratorField)) {
                $values[$name] = $originalId;
            } else if($field instanceof IFieldValueProcessor) {
                $values[$name] = $field->inflateValueFromRow($name, $row, null);
            } else {
                $values[$name] = $originalId;
            }
        }

        return new opal\record\PrimaryKeySet(array_keys($fields), $values);
    }

    protected function _deflateInsertValues(array $row) {
        $adapter = $this->_source->getAdapter();

        if(!$adapter instanceof IIntegralAdapter) {
            return $row;
        }

        $schema = $adapter->getQueryAdapterSchema();
        $values = [];
        
        foreach($schema->getFields() as $name => $field) {
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
                    $values[$key] = $val;
                }
            } else {
                $values[$name] = $value;
            }
        }
        
        return $values;
    }
    
// Dump
    public function getDumpProperties() {
        return [
            'source' => $this->_source->getAdapter(),
            'row' => $this->_row
        ];
    }
}
