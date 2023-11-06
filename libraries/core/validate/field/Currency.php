<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\validate\field;

use DecodeLabs\R7\Mint\Currency as MintCurrency;
use df\core;

class Currency extends Base implements core\validate\ICurrencyField
{
    use core\validate\TRangeField;

    protected $_currency = null;
    protected $_currencySelectable = true;
    protected $_currencyFieldName = null;


    // Options
    public function setCurrency($code)
    {
        if ($code !== null) {
            $code = MintCurrency::normalizeCode($code);
        }

        $this->_currency = $code;
        return $this;
    }

    public function getCurrency()
    {
        return $this->_currency;
    }

    public function setCurrencyFieldName($name)
    {
        $this->_currencyFieldName = $name;
        return $this;
    }

    public function getCurrencyFieldName()
    {
        return $this->_currencyFieldName;
    }

    public function allowSelection(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_currencySelectable = $flag;
            return $this;
        }

        return $this->_currencySelectable;
    }



    // Validate
    public function validate()
    {
        // Sanitize
        $value = $this->_sanitizeValue($this->data->getValue());

        if ($this->_currencySelectable && ($currency = $this->data->currency->getValue())) {
            $this->_currency = $currency;
        }

        if (!$length = $this->_checkRequired($value)) {
            return null;
        }



        // Validate
        if (!filter_var($value, FILTER_VALIDATE_FLOAT, ['decimal' => true]) && $value !== '0') {
            $this->addError('invalid', $this->validator->_(
                'This is not a valid number'
            ));
        }

        $this->_validateRange($value);



        // Finalize
        $this->_applyExtension($value);
        $this->data->setValue($value);

        return $value;
    }


    // Apply
    public function applyValueTo(&$record, $value)
    {
        $output = parent::applyValueTo($record, $value);

        if ($this->_currencyFieldName) {
            $record[$this->_currencyFieldName] = $this->_currency;
        }

        return $output;
    }
}
