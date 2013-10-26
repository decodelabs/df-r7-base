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

    protected $_inputUnit = 'GBP';
    protected $_defaultInputUnit = 'GBP';
    protected $_unitSelectable = false;

    public function __construct(arch\IContext $context, $name, $value=null, $inputUnit=null, $allowSelection=false) {
        $this->_unitSelectable = (bool)$allowSelection;

        if($inputUnit !== null) {
            $this->_defaultInputUnit = $this->_inputUnit = mint\Currency::normalizeCode($inputUnit);
        }

        $this->setStep(0.01);
        parent::__construct($context, $name, $value);
    }

    protected function _render() {
        $name = $this->getName();
        $unitName = $name.'[unit]';
        $context = $this->getRenderTarget()->getContext();
        $selectValue = mint\Currency::normalizeCode($this->_inputUnit);

        $output = parent::_render();

        if($this->_unitSelectable) {
            $value = $this->getValue();
            $output = $output->render();
            $list = $context->i18n->numbers->getCurrencyList();
            $options = array_intersect_key($list, array_flip(mint\Currency::getRecognizedCodes()));

            $output = new aura\html\ElementContent([$output, ' ',
                self::factory($context, 'SelectList', [
                        $unitName,
                        $selectValue,
                        $options
                    ])
                    ->setRenderTarget($this->getRenderTarget())
            ]);
        } else {
            $unit = $context->i18n->numbers->getCurrencyName($this->_inputUnit);
            $output = new aura\html\Element('label', [$output, ' ', 
                new aura\html\Element('abbr', $this->_inputUnit, ['title' => $unit]),
                new aura\html\Element('input', null, [
                    'type' => 'hidden',
                    'name' => $unitName,
                    'value' => $selectValue
                ])
            ]);
        }

        return $output;
    }

    public function getInputUnit() {
        return $this->_inputUnit;
    }

    public function shouldAllowUnitSelection() {
        return $this->_unitSelectable;
    }

    public function setValue($value) {
        $innerValue = $value;

        if($innerValue instanceof core\IValueContainer) {
            if($this->_unitSelectable && isset($innerValue->unit)) {
                $this->_inputUnit = mint\Currency::normalizeCode($innerValue['unit']);
            }

            $innerValue = $innerValue->getValue();
        }

        if(is_string($innerValue) && !strlen($innerValue)) {
            $innerValue = null;
        }

        if($innerValue == 0 && !$this->isRequired()) {
            $innerValue = null;
        }

        if($value instanceof core\IValueContainer) {
            $value->setValue($innerValue);
        } else {
            $value = $innerValue;
        }

        return parent::setValue($value);
    }
}