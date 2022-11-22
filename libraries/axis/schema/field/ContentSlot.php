<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\axis\schema\field;

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;
use df\axis;

use df\fire;
use df\opal;

class ContentSlot extends Base
{
    // Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord = null)
    {
        if (!isset($row[$key])) {
            return null;
        }

        $output = $row[$key];

        if (!$forRecord) {
            return $output;
            //return fire\slot\Content::fromXmlString($output);
        }

        return new opal\record\valueContainer\LazyLoad($output, function ($value, $record, $fieldName) {
            if (empty($value)) {
                $value = null;
            }

            if ($value !== null) {
                return fire\slot\Content::fromXmlString($value);
            }
        });
    }


    public function deflateValue($value)
    {
        if ($value instanceof fire\ISlotContent) {
            return $value->toXmlString(true);
        }

        if ($value === null && $this->isNullable()) {
            return null;
        }

        if (is_string($value)/* && substr($value, 0, 1) == '<'*/) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        throw Exceptional::UnexpectedValue(
            'Content slot value appears to be invalid'
        );
    }

    public function sanitizeClauseValue($value)
    {
        if ($value === null) {
            return null;
        }

        return $this->sanitizeValue($value);
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord = null)
    {
        if ($value === null) {
            if ($this->isNullable()) {
                return null;
            }

            throw Exceptional::UnexpectedValue(
                'Content slot value cannot be null'
            );
        }

        if (!$value instanceof fire\ISlotContent) {
            try {
                $value = fire\slot\Content::fromXml($value);
            } catch (\Throwable $e) {
            }
        }

        if ($value instanceof fire\ISlotContent) {
            // check slot validity
        }

        return $value;
    }

    public function compareValues($value1, $value2)
    {
        if ($value1 === null && $value2 === null) {
            return true;
        }

        $value1 = $this->_extractFromLazyLoader($value1);
        $value2 = $this->_extractFromLazyLoader($value2);

        if ($value1 === $value2) {
            // if they're the same object, assume it's changed
            return false;
        }

        if (!$value1 instanceof fire\ISlotContent
        || !$value2 instanceof fire\ISlotContent) {
            return false;
        }

        /*
        if($value1->hasChanged() || $value2->hasChanged()) {
            return false;
        }
         */

        if ($value1->getId() != $value2->getId()) {
            return false;
        }

        return Dictum::compare(
            $value1->toXmlString(true),
            $value2->toXmlString(true)
        );
    }

    private function _extractFromLazyLoader($value)
    {
        if ($value instanceof opal\record\valueContainer\LazyLoad) {
            if ($value->isPrepared()) {
                $value = $value->getValue();
            } else {
                $value = $value->getValueForStorage();
            }
        }

        if (empty($value)) {
            return null;
        }

        if (is_string($value)) {
            $value = fire\slot\Content::fromXml($value);
        }

        return $value;
    }

    public function getSearchFieldType()
    {
        return 'string';
    }


    // Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema)
    {
        return (new opal\schema\Primitive_Text($this, opal\schema\IFieldSize::HUGE))
            ->setCharacterSet('utf8');
    }
}
