<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use DecodeLabs\R7\Mint\Currency as MintCurrency;
use df\arch;
use df\aura;
use df\core;

class Currency extends NumberTextbox
{
    public const PRIMARY_TAG = 'input.textbox.number.currency';

    protected $_inputCurrency = 'GBP';
    protected $_currencySelectable = false;
    protected $_showCurrency = true;
    protected $_precision = 2;

    public function __construct(arch\IContext $context, $name, $value = null, string $inputCurrency = null, bool $allowSelection = false, int $precision = 2)
    {
        $this->_currencySelectable = (bool)$allowSelection;

        if ($inputCurrency !== null) {
            $this->_inputCurrency = MintCurrency::normalizeCode($inputCurrency);
        }

        $this->_precision = $precision;
        $this->setStep(1 / pow(10, $precision));

        parent::__construct($context, $name, $value);
    }

    protected function _render()
    {
        $currencyFieldName = $this->getName() . '[currency]';
        $selectValue = MintCurrency::normalizeCode($this->_inputCurrency);

        $output = parent::_render();

        if ($this->_currencySelectable) {
            $value = $this->getValue();
            $output = $output->render();
            $list = $this->_context->i18n->numbers->getCurrencyList();
            $options = array_intersect_key($list, array_flip(MintCurrency::getRecognizedCodes()));

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

    public function getPrecision(): int
    {
        return $this->_precision;
    }

    public function getInputCurrency(): ?string
    {
        return $this->_inputCurrency;
    }

    public function allowSelection(): bool
    {
        return $this->_currencySelectable;
    }

    public function shouldShowCurrency(bool $flag = null)
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
                $this->_inputCurrency = MintCurrency::normalizeCode($value['currency']);
            }
        }

        $number = $value->getValue();

        if ($number !== null && is_numeric($number)) {
            $number = number_format((float)str_replace(',', '', (string)$number), $this->_precision, '.', '');
            $number = preg_replace('/\.0+$/', '', $number);
        }

        $value->setValue($number);
    }
}
