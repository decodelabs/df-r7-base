<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
    
class Currency extends Base implements core\validate\ICurrencyField {

    use core\validate\TSanitizingField;
    use core\validate\TRangeField;

    protected $_inputUnit = null;
    protected $_unitSelectable = true;
    protected $_unitFieldName = null;

    public function setInputUnit($unit) {
        if($unit !== null) {
            $unit = mint\Currency::normalizeCode($unit);
        }

        $this->_inputUnit = $unit;
        return $this;
    }

    public function getInputUnit() {
        return $this->_inputUnit;
    }

    public function shouldAllowUnitSelection($flag=null) {
        if($flag !== null) {
            $this->_unitSelectable = (bool)$flag;
            return $this;
        }

        return $this->_unitSelectable;
    }

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);

        if($this->_unitSelectable && ($unit = $node->unit->getValue())) {
            $this->_inputUnit = $unit;
        }

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        if(!filter_var($value, FILTER_VALIDATE_FLOAT, ['decimal' => true]) && $value !== '0') {
            $node->addError('invalid', $this->_handler->_(
                'This is not a valid number'
            ));
        }

        $this->_validateRange($node, $value);
        return $this->_finalize($node, $value);
    }

    public function setUnitFieldName($name) {
        $this->_unitFieldName = $name;
        return $this;
    }

    public function getUnitFieldName() {
        return $this->_unitFieldName;
    }

    public function applyValueTo(&$record, $value) {
        $output = parent::applyValueTo($record, $value);

        if($this->_unitFieldName) {
            $record[$this->_unitFieldName] = $this->_inputUnit;
        }

        return $output;
    }
}