<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\validate\field;

use df;
use df\core;
use df\mint;
    
class Currency extends Base implements core\validate\ICurrencyField {

    use core\validate\TSanitizingField;
    use core\validate\TRangeField;

    protected $_currency = null;
    protected $_currencySelectable = true;
    protected $_currencyFieldName = null;

    public function setCurrency($code) {
        if($code !== null) {
            $code = mint\Currency::normalizeCode($code);
        }

        $this->_currency = $code;
        return $this;
    }

    public function getCurrency() {
        return $this->_currency;
    }

    public function allowCurrencySelection($flag=null) {
        if($flag !== null) {
            $this->_currencySelectable = (bool)$flag;
            return $this;
        }

        return $this->_currencySelectable;
    }

    public function validate(core\collection\IInputTree $node) {
        $value = $node->getValue();
        $value = $this->_sanitizeValue($value);

        if($this->_currencySelectable && ($currency = $node->currency->getValue())) {
            $this->_currency = $currency;
        }

        if(!$length = $this->_checkRequired($node, $value)) {
            return null;
        }

        if(!filter_var($value, FILTER_VALIDATE_FLOAT, ['decimal' => true]) && $value !== '0') {
            $this->_applyMessage($node, 'invalid', $this->_handler->_(
                'This is not a valid number'
            ));
        }

        $this->_validateRange($node, $value);
        return $this->_finalize($node, $value);
    }

    public function setCurrencyFieldName($name) {
        $this->_currencyFieldName = $name;
        return $this;
    }

    public function getCurrencyFieldName() {
        return $this->_currencyFieldName;
    }

    public function applyValueTo(&$record, $value) {
        $output = parent::applyValueTo($record, $value);

        if($this->_currencyFieldName) {
            $record[$this->_currencyFieldName] = $this->_currency;
        }

        return $output;
    }
}