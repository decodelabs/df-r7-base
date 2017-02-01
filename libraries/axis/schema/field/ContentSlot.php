<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema\field;

use df;
use df\core;
use df\axis;
use df\fire;
use df\opal;
use df\flex;

class ContentSlot extends Base {

    protected $_category;
    protected $_slotDefinition;

    protected function _init($category=null) {
        $this->setCategory($category);
    }

// Category
    public function setCategory($category) {
        if(empty($category)) {
            $category = null;
        }

        if($category !== null) {
            fire\category\Base::factory($category);
        }

        if($category != $this->_category) {
            $this->_hasChanged = true;
        }

        $this->_category = $category;
        return $this;
    }

    public function getCategory() {
        return $this->_category;
    }


// Slot definition
    public function setSlotDefinition(fire\slot\IDefinition $definition) {
        if($definition !== $this->_slotDefinition) {
            $this->_hasChanged = true;
        }

        $this->_slotDefinition = $definition;
        return $this;
    }

    public function getSlotDefinition() {
        if($this->_slotDefinition) {
            return $this->_slotDefinition;
        }

        $output = new fire\slot\Definition();
        $output->setMinBlocks(1);
        $output->setCategory($this->_category);

        return $output;
    }



// Values
    public function inflateValueFromRow($key, array $row, opal\record\IRecord $forRecord=null) {
        if(!isset($row[$key])) {
            return null;
        }

        $output = $row[$key];

        if(!$forRecord) {
            return $output;
            //return fire\slot\Content::fromXmlString($output);
        }

        return new opal\record\valueContainer\LazyLoad($output, function($value, $record, $fieldName) {
            if(empty($value)) {
                $value = null;
            }

            if($value !== null) {
                return fire\slot\Content::fromXmlString($value);
            }
        });
    }


    public function deflateValue($value) {
        if($value instanceof fire\slot\IContent) {
            return $value->toXmlString(true);
        }

        if($value === null && $this->isNullable()) {
            return null;
        }

        if(is_string($value)/* && substr($value, 0, 1) == '<'*/) {
            return $value;
        }

        if(is_string($value)) {
            return $value;
        }

        if($value === null) {
            return null;
        }

        throw new axis\schema\UnexpectedValueException(
            'Content slot value appears to be invalid'
        );
    }

    public function sanitizeClauseValue($value) {
        if($value === null) {
            return null;
        }

        return $this->sanitizeValue($value);
    }

    public function sanitizeValue($value, opal\record\IRecord $forRecord=null) {
        if($value === null) {
            if($this->isNullable()) {
                return null;
            }

            throw new axis\schema\UnexpectedValueException(
                'Content slot value cannot be null'
            );
        }

        if(!$value instanceof fire\slot\IContent) {
            try {
                $value = fire\slot\Content::fromXml($value);
            } catch(\Exception $e) {}
        }

        if($value instanceof fire\slot\IContent) {
            // check slot validity
        }

        return $value;
    }

    public function compareValues($value1, $value2) {
        if($value1 === null && $value2 === null) {
            return true;
        }

        $value1 = $this->_extractFromLazyLoader($value1);
        $value2 = $this->_extractFromLazyLoader($value2);

        if($value1 === $value2) {
            // if they're the same object, assume it's changed
            return false;
        }

        if(!$value1 instanceof fire\slot\IContent
        || !$value2 instanceof fire\slot\IContent) {
            return false;
        }

        /*
        if($value1->hasChanged() || $value2->hasChanged()) {
            return false;
        }
        */

        if($value1->getId() != $value2->getId()) {
            return false;
        }

        return flex\Text::compare(
            $value1->toXmlString(true),
            $value2->toXmlString(true)
        );
    }

    private function _extractFromLazyLoader($value) {
        if($value instanceof opal\record\valueContainer\LazyLoad) {
            if($value->isPrepared()) {
                $value = $value->getValue();
            } else {
                $value = $value->getValueForStorage();
            }
        }

        if(empty($value)) {
            return null;
        }

        if(is_string($value)) {
            $value = fire\slot\Content::fromXml($value);
        }

        return $value;
    }

    public function getSearchFieldType() {
        return 'string';
    }


// Primitive
    public function toPrimitive(axis\ISchemaBasedStorageUnit $unit, axis\schema\ISchema $schema) {
        return (new opal\schema\Primitive_Text($this, opal\schema\IFieldSize::HUGE))
            ->setCharacterSet('utf8');
    }


// Ext. serialize
    protected function _importStorageArray(array $data) {
        $this->_setBaseStorageArray($data);

        $this->_category = $data['cat'];

        if(isset($data['sld'])) {
            $this->_slotDefinition = fire\slot\Definition::fromArray($data['sld']);
        }
    }

    public function toStorageArray() {
        return array_merge(
            $this->_getBaseStorageArray(),
            [
                'cat' => $this->_category,
                'sld' => $this->_slotDefinition ?
                    $this->_slotDefinition->toArray() :
                    null
            ]
        );
    }
}