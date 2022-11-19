<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Dumpable;

use df\core;
use df\opal;

class Update implements IUpdateQuery, Dumpable
{
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Locational;
    use TQuery_PrerequisiteClauseFactory;
    use TQuery_WhereClauseFactory;
    use TQuery_Orderable;
    use TQuery_Limitable;
    use TQuery_Write;

    protected $_values = [];
    protected $_preparedValues;

    public function __construct(ISourceManager $sourceManager, ISource $source, array $values = null)
    {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;

        if ($values !== null) {
            $this->set($values);
        }
    }

    public function getQueryType()
    {
        return IQueryTypes::UPDATE;
    }

    public function set($key, $value = null)
    {
        if (is_array($key)) {
            $values = $key;
        } else {
            $values = [$key => $value];
        }

        $this->_values = array_merge($this->_values, $values);
        $this->_preparedValues = null;
        return $this;
    }

    public function express($field, ...$elements)
    {
        return $this->beginExpression($field, ...$elements)->endExpression();
    }

    public function beginExpression($field, ...$elements)
    {
        return new Expression($this, $field, $elements);
    }

    public function expressCorrelation($field, $targetField)
    {
        Glitch::incomplete([$field, $targetField]);
    }


    public function getValues()
    {
        return $this->_values;
    }

    public function getPreparedValues()
    {
        if (!$this->_preparedValues) {
            $this->_preparedValues = $this->_deflateUpdateValues($this->_values);
        }

        return $this->_preparedValues;
    }


    // Execute
    public function execute()
    {
        $adapter = $this->_source->getAdapter();
        $this->getPreparedValues();

        if (empty($this->_preparedValues)) {
            $this->_preparedValues = null;
            return 0;
        }

        $output = $this->_sourceManager->executeQuery($this, function ($adapter) {
            return $adapter->executeUpdateQuery($this);
        });

        $this->_preparedValues = null;
        return $output;
    }

    protected function _deflateUpdateValues(array $values)
    {
        $adapter = $this->_source->getAdapter();

        if (!$adapter instanceof IIntegralAdapter) {
            return $values;
        }

        $schema = $adapter->getQueryAdapterSchema();

        foreach ($values as $name => $value) {
            if ($value instanceof IExpression) {
                continue;
            }

            if (!$field = $schema->getField($name)) {
                continue;
            }

            if ($field instanceof opal\schema\INullPrimitiveField) {
                unset($values[$name]);
                continue;
            }

            if ($field instanceof opal\schema\IAutoTimestampField
            && ($value === null || $value === '')
            && !$field->isNullable()) {
                $value = new core\time\Date();
            }

            $value = $field->deflateValue($field->sanitizeValue($value));

            if (is_array($value)) {
                unset($values[$name]);

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
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*source' => $this->_source->getAdapter(),
            '*values' => $this->_values
        ];

        if ($this->hasWhereClauses()) {
            yield 'property:*where' => $this->getWhereClauseList();
        }

        if (!empty($this->_order)) {
            $order = [];

            foreach ($this->_order as $directive) {
                $order[] = $directive->toString();
            }

            yield 'property:*order' => implode(', ', $order);
        }

        if ($this->_limit !== null) {
            yield 'property:*limit' => $this->_limit;
        }
    }
}
