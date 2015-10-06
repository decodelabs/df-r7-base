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
use df\flex;

class KeyString extends Text {

    protected $_case = flex\ICase::NONE;

    protected function _init($length=16, $case=flex\ICase::NONE) {
        $this->setLength($length);
        $this->setCase($case);
    }

    public function setCase($case) {
        $case = flex\Text::normalizeCaseFlag($case);

        if($case != $this->_case) {
            $this->_hasChanged = true;
        }

        $this->_case = $case;
        return $this;
    }

    public function getCase() {
        return $this->_case;
    }


// Values
    public function sanitizeValue($value, opal\record\IRecord $forRecord=null) {
        if($this->_case != flex\ICase::NONE && $value !== null) {
            $value = flex\Text::applyCase($value, $this->_case, $this->_characterSet);
        }

        return $value;
    }

    public function compareValues($value1, $value2) {
        return (string)$value1 === (string)$value2;
    }

    public function getSearchFieldType() {
        return 'string';
    }

// Ext. serialize
    protected function _importStorageArray(array $data) {
        parent::_importStorageArray($data);
        $this->_case = $data['cas'];
    }

    public function toStorageArray() {
        $output = parent::toStorageArray();
        $output['cas'] = $this->_case;

        return $output;
    }
}