<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df;
use df\core;
use df\axis;
use df\opal;

class Number extends Base implements
    opal\schema\IByteSizeRestrictedField,
    opal\schema\INumericField,
    opal\schema\IFloatingPointNumericField {

    use opal\schema\TField_ByteSizeRestricted;
    //use opal\schema\TField_Numeric;
    use opal\schema\TField_FloatingPointNumeric;

    protected $_isFixedPoint = false;

    protected function _initAsInteger($size=null) {
        $this->setByteSize($size);
    }

    protected function _initAsUInteger($size=null) {
        $this->setByteSize($size);
        $this->isUnsigned(true);
    }

    protected function _initAsFilled($precision=6) {
        $this->setPrecision($precision);
        $this->setScale(0);
        $this->isFixedPoint(true);
        $this->isUnsigned(true);
        $this->shouldZerofill(true);
    }

    protected function _initAsFloat($precision=null, $scale=null) {
        $this->setPrecision($precision);
        $this->setScale($scale);
    }

    protected function _initAsUFloat($precision=null, $scale=null) {
        $this->setPrecision($precision);
        $this->setScale($scale);
        $this->isUnsigned(true);
    }

    protected function _initAsDecimal($precision=null, $scale=null) {
        $this->setPrecision($precision);
        $this->setScale($scale);
        $this->isFixedPoint(true);
    }

    protected function _initAsUDecimal($precision=null, $scale=null) {
        $this->setPrecision($precision);
        $this->setScale($scale);
        $this->isFixedPoint(true);
        $this->isUnsigned(true);
    }

    protected function _initAsCurrency() {
        $this->setPrecision(24);
        $this->setScale(4);
        $this->isFixedPoint(true);
    }

    protected function _initAsPercentage($scale=4) {
        $this->setScale($scale);
        $this->setPrecision($this->_scale + 3);
        $this->isFixedPoint(true);
        $this->isUnsigned(true);
    }

    protected function _initAsLatLong() {
        $this->setPrecision(10);
        $this->setScale(6);
    }

    protected function _init($size=null) {
        $this->setByteSize($size);
    }


    public function isFixedPoint(bool $flag=null) {
        if($flag !== null) {
            if($flag != $this->_isFixedPoint) {
                $this->_hasChanged = true;
            }

            $this->_isFixedPoint = $flag;
            return $this;
        }

        return $this->_isFixedPoint;
    }


// Values
    public function sanitizeValue($value, opal\record\IRecord $forRecord=null) {
        if($value !== null) {
            if($this->_isFixedPoint || $this->_zerofill) {
                $value = (string)$value;
            } else if($this->_byteSize) {
                $value = (int)$value;
            } else {
                $value = (double)$value;
            }
        }

        return $value;
    }

    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null) {
        if(isset($row[$key])) {
            $output = $row[$key];
        } else {
            $output = null;//$this->_defaultValue;
        }

        return $this->sanitizeValue($output, $forRecord);
    }


    public function compareValues($value1, $value2) {
        $value1 = $this->sanitizeValue($value1);
        $value2 = $this->sanitizeValue($value2);

        if($value1 === null) {
            return $value2 === null;
        } else if($value2 === null) {
            return false;
        }

        if($this->_byteSize || $this->_isFixedPoint || $this->_zerofill) {
            return $value1 === $value2;
        } else {
            // TODO: Use precision setting to define comparison value
            return abs($value1 - $value2) < 0.00001;
        }
    }

    public function getSearchFieldType() {
        if($this->_byteSize) {
            return 'integer';
        } else {
            return 'float';
        }
    }

// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        if($this->_byteSize) {
            $output = new opal\schema\Primitive_Integer($this, $this->_byteSize);
        } else if($this->_isFixedPoint) {
            $output = new opal\schema\Primitive_Decimal($this, $this->_precision, $this->_scale);
        } else {
            $output = new opal\schema\Primitive_Float($this, $this->_precision, $this->_scale);
        }

        if($this->_isUnsigned) {
            $output->isUnsigned(true);
        }

        if($this->_zerofill) {
            $output->shouldZerofill(true);
        }

        return $output;
    }


// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);
        $this->_setByteSizeRestrictedStorageArray($data);
        $this->_setFloatingPointNumericStorageArray($data);

        if(isset($data['fpt'])) {
            $this->_isFixedPoint = $data['fpt'];
        } else {
            $this->_isFixedPoint = false;
        }
    }

    public function toStorageArray() {
        $output = array_merge(
            $this->_getBaseStorageArray(),
            $this->_getByteSizeRestrictedStorageArray(),
            $this->_getFloatingPointNumericStorageArray()
        );

        if($this->_isFixedPoint) {
            $output['fpt'] = true;
        }

        return $output;
    }
}
