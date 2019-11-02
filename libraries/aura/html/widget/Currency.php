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

class Currency extends NumberTextbox
{
    const PRIMARY_TAG = 'input.textbox.number.currency';

    protected $_inputCurrency = 'GBP';
    protected $_currencySelectable = false;
    protected $_showCurrency = true;

    public function __construct(arch\IContext $context, $name, $value=null, string $inputCurrency=null, bool $allowSelection=false)
    {
        $this->_currencySelectable = (bool)$allowSelection;

        if ($inputCurrency !== null) {
            $this->_inputCurrency = mint\Currency::normalizeCode($inputCurrency);
        }

        $this->setStep(0.01);
        parent::__construct($context, $name, $value);
    }

    protected function _render()
    {
        $currencyFieldName = $this->getName().'[currency]';
        $selectValue = mint\Currency::normalizeCode($this->_inputCurrency);

        $output = parent::_render();

        if ($this->_currencySelectable) {
            $value = $this->getValue();
            $output = $output->render();
            $list = $this->_context->i18n->numbers->getCurrencyList();
            $options = array_intersect_key($list, array_flip(mint\Currency::getRecognizedCodes()));

            $output = new aura\html\ElementContent([$output, ' ',
                self::factory($this->_context, 'Select', [
                    $currencyFieldName,
                    $selectValue,
                    $options
                ])->addClass('currency')
            ]);
        } elseif ($this->_showCurrency) {
            $currency = $this->_context->i18n->numbers->getCurrencyName($this->_inputCurrency);
            $output = new aura\html\Element('label.currency', [$output, ' ',
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

    public function getInputCurrency(): ?string
    {
        return $this->_inputCurrency;
    }

    public function allowSelection(): bool
    {
        return $this->_currencySelectable;
    }

    public function shouldShowCurrency(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_showCurrency = $flag;
            return $this;
        }

        return $this->_showCurrency;
    }


    protected function _normalizeValue(core\collection\IInputTree $value)
    {
        if ($value instanceof core\IValueContainer) {
            if ($this->_currencySelectable && isset($value->currency)) {
                $this->_inputCurrency = mint\Currency::normalizeCode($value['currency']);
            }
        }

        $number = $value->getValue();

        if ($number !== null && is_numeric($number)) {
            $number = number_format((float)str_replace(',', '', (string)$number), 2, '.', '');
            $number = str_replace('.00', '', $number);
        }

        $value->setValue($number);
    }
}
