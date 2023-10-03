<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\rdbms\variant\sqlite;

use df\opal;

class Trigger extends opal\rdbms\schema\constraint\Trigger
{
    protected $_isTemporary = false;
    protected $_updateFields = [];
    protected $_whenExpression;

    public function isTemporary(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isTemporary = $flag;
            return $this;
        }

        return $this->_isTemporary;
    }

    public function setUpdateFields(array $fields)
    {
        $this->_updateFields = $fields;
        return $this;
    }

    public function getUpdateFields()
    {
        return $this->_updateFields;
    }

    public function setWhenExpression($expression)
    {
        $this->_whenExpression = $expression;
        return $this;
    }

    public function getWhenExpression()
    {
        return $this->_whenExpression;
    }

    protected function _hasFieldReference(array $fields)
    {
        $regex = '/(OLD|NEW)[`]?\.[`]?(' . implode('|', $fields) . ')[`]?/i';

        foreach ($this->_statements as $statement) {
            if (preg_match($regex, (string)$statement)) {
                return true;
            }
        }

        return false;
    }


    // Ext. serialize
    public function toStorageArray()
    {
        return array_merge(
            $this->_getGenericStorageArray(),
            [
                'tmp' => $this->_isTemporary,
                'udf' => $this->_updateFields,
                'wex' => $this->_whenExpression
            ]
        );
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        $output = '';

        if ($this->_isTemporary) {
            $output .= 'TEMP ';
        }

        $output .= $this->_name;
        $output .= ' ' . $this->getTimingName();
        $output .= ' ' . $this->getEventName();

        if (!empty($this->_updateFields)) {
            $output .= ' OF ' . implode(', ', $this->_updateFields);
        }

        if ($this->_whenExpression !== null) {
            $output .= ' WHEN ' . $this->_whenExpression;
        }

        $output .= ' ' . implode('; ', $this->_statements);
        $output .= ' [' . $this->_sqlVariant . ']';

        yield 'definition' => $output;
    }
}
