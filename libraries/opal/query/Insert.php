<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Dumpable;

class Insert implements IInsertQuery, Dumpable
{
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Locational;
    use TQuery_DataInsert;
    use TQuery_Write;

    protected $_row;
    protected $_preparedRow;

    public function __construct(ISourceManager $sourceManager, ISource $source, $row, $shouldReplace=false)
    {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
        $this->_shouldReplace = (bool)$shouldReplace;

        $this->setRow($row);
    }

    public function getQueryType()
    {
        if ($this->_shouldReplace) {
            return IQueryTypes::REPLACE;
        } else {
            return IQueryTypes::INSERT;
        }
    }

    public function setRow($row)
    {
        if ($this instanceof ILocationalQuery && $row instanceof opal\record\ILocationalRecord) {
            $this->inside($row->getQueryLocation());
        }

        if ($row instanceof IDataRowProvider) {
            $row = $row->toDataRowArray();
        } elseif ($row instanceof core\IArrayProvider) {
            $row = $row->toArray();
        } elseif (!is_array($row)) {
            throw Glitch::EInvalidArgument(
                'Insert data must be convertible to an array'
            );
        }

        if (empty($row)) {
            throw Glitch::EInvalidArgument(
                'Insert data must contain at least one field'
            );
        }

        $this->_preparedRow = null;
        $this->_row = $row;
        return $this;
    }

    public function getRow()
    {
        return $this->_row;
    }

    public function getPreparedRow()
    {
        if (!$this->_preparedRow) {
            $this->_preparedRow = $this->_deflateInsertValues($this->_row);
        }

        return $this->_preparedRow;
    }


    public function execute()
    {
        $output = $this->_sourceManager->executeQuery($this, function ($adapter) {
            return $adapter->executeInsertQuery($this);
        });

        $output = $this->_normalizeInsertId($output, $this->_preparedRow);
        $this->_preparedRow = null;

        return $output;
    }

    protected function _normalizeInsertId($originalId, array $row)
    {
        $adapter = $this->_source->getAdapter();

        if (!$adapter instanceof IIntegralAdapter) {
            return $originalId;
        }

        $index = $adapter->getQueryAdapterSchema()->getPrimaryIndex();

        if (!$index) {
            return $originalId;
        }

        $fields = $index->getFields();
        $values = [];

        foreach ($fields as $name => $field) {
            if ($originalId
            && $field instanceof opal\schema\IAutoIncrementableField
            && $field->shouldAutoIncrement()) {
                $values[$name] = $originalId;

                if ($field instanceof IFieldValueProcessor) {
                    $values[$name] = $field->inflateValueFromRow($name, $values, null);
                }
            } elseif ($field instanceof IFieldValueProcessor) {
                $values[$name] = $field->inflateValueFromRow($name, $row, null);
            } elseif (isset($row[$name])) {
                $values[$name] = $row[$name];
            } else {
                $values[$name] = null;
            }
        }

        return new opal\record\PrimaryKeySet(array_keys($fields), $values);
    }

    protected function _deflateInsertValues(array $row)
    {
        $adapter = $this->_source->getAdapter();

        if (!$adapter instanceof IIntegralAdapter) {
            return $row;
        }

        $schema = $adapter->getQueryAdapterSchema();
        $values = [];

        foreach ($schema->getFields() as $name => $field) {
            if ($field instanceof opal\schema\INullPrimitiveField) {
                continue;
            }

            if (!isset($row[$name])) {
                $this->_row[$name] = $value = $field->generateInsertValue($row);
            } else {
                $value = $field->sanitizeValue($row[$name]);
            }

            if ($field instanceof opal\schema\IAutoTimestampField
            && ($value === null || $value === '')
            && $field->shouldTimestampAsDefault()) {
                continue;
            }

            $value = $field->deflateValue($value);

            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $values[$key] = $val;
                }
            } else {
                $values[$name] = $value;
            }
        }

        return $values;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*source' => $this->_source->getAdapter(),
            '*row' => $this->_row
        ];
    }
}
