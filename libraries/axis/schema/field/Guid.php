<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis\schema\field;

use DecodeLabs\Guidance;
use DecodeLabs\Guidance\Uuid;
use df\axis;
use df\flex;
use df\opal;

class Guid extends Base implements opal\schema\IAutoGeneratorField
{
    use opal\schema\TAutoGeneratorField;

    public const UUID1 = 1;
    public const UUID4 = 2;
    public const COMB = 3;

    protected $_generator = self::COMB;

    public function setGenerator($gen)
    {
        if (is_string($gen)) {
            switch (strtolower($gen)) {
                case 'uuid':
                case 'uuid4':
                    $gen = self::UUID4;
                    break;

                case 'uuid1':
                    $gen = self::UUID1;
                    break;

                case 'comb':
                    $gen = self::COMB;
                    break;
            }
        }

        switch ($gen) {
            case self::UUID1:
            case self::UUID4:
                break;

            case self::COMB:
            default:
                $gen = self::COMB;
                break;
        }

        if ($this->_generator !== $gen) {
            $this->_hasChanged = true;
        }

        $this->_generator = $gen;

        return $this;
    }

    public function getGenerator()
    {
        return $this->_generator;
    }

    public function getGeneratorName()
    {
        switch ($this->_generator) {
            case self::UUID1:
                return 'UUID v1';

            case self::UUID4:
                return 'UUID v4';

            case self::COMB:
                return 'Comb';
        }
    }


    // Values
    public function inflateValueFromRow(
        $key,
        array $row,
        opal\record\IRecord $forRecord = null
    ) {
        if (isset($row[$key]) && !empty($row[$key])) {
            return Guidance::from($row[$key]);
        } else {
            return null;
        }
    }

    public function deflateValue($value)
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Uuid) {
            $value = Guidance::from($value);
        }

        return $value->getBytes();
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if (!$value instanceof Uuid) {
            $value = (string)$value;

            if (!strlen($value)) {
                $value = null;
            }
        }

        if ($value !== null) {
            try {
                $value = Guidance::from($value);
            } catch (flex\Exception $e) {
                $value = Guidance::createVoid();
            }
        }

        return $value;
    }

    public function compareValues($value1, $value2)
    {
        return (string)$value1 === (string)$value2;
    }

    public function generateInsertValue(array $row)
    {
        if (!$this->_autoGenerate) {
            return null;
        }

        if (array_key_exists($this->_name, $row) && $this->isNullable()) {
            return null;
        }

        if ($this->_defaultValue !== null) {
            return $this->_defaultValue;
        }

        return $this->getNominalValue();
    }

    public function getNominalValue()
    {
        switch ($this->_generator) {
            case self::UUID1:
                return Guidance::createV1();

            case self::UUID4:
                return Guidance::createV4();

            case self::COMB:
                return Guidance::createV4Comb();
        }
    }

    public function getSearchFieldType()
    {
        return 'guid';
    }


    // Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        return new opal\schema\Primitive_Guid($this, $this->_generator);
    }


    // Ext. serialize
    protected function _importStorageArray(array $data)
    {
        $this->_setBaseStorageArray($data);
        $this->_generator = $data['gen'];
    }

    public function toStorageArray()
    {
        return array_merge(
            $this->_getBaseStorageArray(),
            ['gen' => $this->_generator]
        );
    }

    // Dump
    public function getFieldTypeDisplayName()
    {
        return 'Guid [' . $this->getGeneratorName() . ']';
    }
}
