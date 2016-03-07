<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;
use df\mint;

class Currency extends NumberTextbox {

    protected $_inputCurrency = 'GBP';
    protected $_currencySelectable = false;
    protected $_showCurrency = true;

    public function __construct(arch\IContext $context, $name, $value=null, $inputCurrency=null, $allowSelection=false) {
        $this->_currencySelectable = (bool)$allowSelection;

        if($inputCurrency !== null) {
            $this->_inputCurrency = mint\Currency::normalizeCode($inputCurrency);
        }

        $this->setStep(0.01);
        parent::__construct($context, $name, $value);
    }

    protected function _render() {
        $currencyFieldName = $this->getName().'[currency]';
        $selectValue = mint\Currency::normalizeCode($this->_inputCurrency);

        $output = parent::_render();

        if($this->_currencySelectable) {
            $value = $this->getValue();
            $output = $output->render();
            $list = $this->_context->i18n->numbers->getCurrencyList();
            $options = array_intersect_key($list, array_flip(mint\Currency::getRecognizedCodes()));

            $output = new aura\html\ElementContent([$output, ' ',
                self::factory($this->_context, 'SelectList', [
                    $currencyFieldName,
                    $selectValue,
                    $options
                ])
            ]);
        } else if($this->_showCurrency) {
            $currency = $this->_context->i18n->numbers->getCurrencyName($this->_inputCurrency);
            $output = new aura\html\Element('label', [$output, ' ',
                new aura\html\Element('abbr', $this->_inputCurrency, ['title' => $currency]),
                new aura\html\Element('input', null, [
                    'type' => 'hidden',
                    'name' => $currencyFieldName,
                    'value' => $selectValue
                ])
            ]);
        }

        return $output;
    }

    public function getInputCurrency() {
        return $this->_inputCurrency;
    }

    public function allowSelection() {
        return $this->_currencySelectable;
    }

    public function shouldShowCurrency(bool $flag=null) {
        if($flag !== null) {
            $this->_showCurrency = $flag;
            return $this;
        }

        return $this->_showCurrency;
    }


    public function setValue($value) {
        $innerValue = $value;

        if($innerValue instanceof core\IValueContainer) {
            if($this->_currencySelectable && isset($innerValue->currency)) {
                $this->_inputCurrency = mint\Currency::normalizeCode($innerValue['currency']);
            }

            $innerValue = $innerValue->getValue();
        }

        if(is_string($innerValue) && !strlen($innerValue)) {
            $innerValue = null;
        }

        if($innerValue == 0 && !$this->isRequired()) {
            $innerValue = null;
        }

        if($innerValue) {
            $innerValue = round(str_replace(',', '', $innerValue), 2);
        }

        if($value instanceof core\IValueContainer) {
            $value->setValue($innerValue);
        } else {
            $value = $innerValue;
        }

        return parent::setValue($value);
    }
}