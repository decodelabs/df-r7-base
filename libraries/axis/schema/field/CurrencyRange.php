<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df\axis;
use df\opal;

class CurrencyRange extends Base implements opal\schema\IMultiPrimitiveField
{
    protected $_requireLowPoint = true;
    protected $_requireHighPoint = true;

    protected function _init($requireLowPoint = true, $requireHighPoint = true)
    {
        $this->requireLowPoint($requireLowPoint);
        $this->requireHighPoint($requireHighPoint);
    }

    public function requireLowPoint(bool $flag = null)
    {
        if ($flag !== null) {
            $flag = $flag;

            if ($flag != $this->_requireLowPoint) {
                $this->_hasChanged = true;
            }

            $this->_requireLowPoint = true;
            return $this;
        }

        return $this->_requireLowPoint;
    }

    public function requireHighPoint(bool $flag = null)
    {
        if ($flag !== null) {
            $flag = $flag;

            if ($flag != $this->_requireHighPoint) {
                $this->_hasChanged = true;
            }

            $this->_requireHighPoint = $flag;
            return $this;
        }

        return $this->_requireHighPoint;
    }


    // Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord = null)
    {
        if (isset($row[$key . '_lo'])) {
            return [$row[$key . '_lo'], $row[$key . '_hi']];
        } else {
            return null;
        }
    }

    public function deflateValue($value)
    {
        return [
            $this->_name . '_lo' => array_shift($value),
            $this->_name . '_hi' => array_shift($value)
        ];
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if (!is_array($value)) {
            $value = [(string)$value, null];
        }

        return $value;
    }


    // Primitive
    public function getPrimitiveFieldNames()
    {
        return [
            $this->_name . '_lo',
            $this->_name . '_hi'
        ];
    }

    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        return new opal\schema\Primitive_MultiField($this, [
            $this->_name . '_lo' => (new opal\schema\Primitive_Decimal($this, 24, 4))
                ->isNullable($this->_isNullable || !$this->_requireLowPoint),
            $this->_name . '_hi' => (new opal\schema\Primitive_Decimal($this, 24, 4))
                ->isNullable($this->_isNullable || !$this->_requireHighPoint)
        ]);
    }

    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);

        $this->_requireLowPoint = $data['rlp'];
        $this->_requireHighPoint = $data['rhp'];
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            [
                'rlp' => $this->_requireLowPoint,
                'rhp' => $this->_requireHighPoint
            ]
        );
    }
}
