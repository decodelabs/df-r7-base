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
    
class DurationUnit extends NumberTextbox {

    protected $_inputUnit = core\time\Duration::SECONDS;
    protected $_defaultInputUnit = core\time\Duration::SECONDS;
    protected $_unitSelectable = false;

    public function __construct(arch\IContext $context, $name, $value=null, $inputUnit=null, $allowSelection=false) {
        $this->_unitSelectable = (bool)$allowSelection;

        if($inputUnit !== null) {
            $this->_defaultInputUnit = $this->_inputUnit = core\time\Duration::normalizeUnitId($inputUnit);
        }

        $this->setStep(0.01);

        parent::__construct($context, $name, $value);
    }

    protected function _render() {
        $name = $this->getName();
        $unitName = $name.'[unit]';
        $context = $this->getRenderTarget()->getContext();
        $selectValue = core\time\Duration::normalizeUnitId($this->_inputUnit);

        $output = parent::_render();

        if($this->_unitSelectable) {
            $value = $this->getValue();
            $output = $output->render();

            $output = new aura\html\ElementContent([$output, ' ', 
                self::factory($context, 'SelectList', [
                        $unitName, 
                        $selectValue, 
                        core\time\Duration::getUnitList($context->getLocale())
                    ])
                    ->setRenderTarget($this->getRenderTarget())
            ]);
        } else if($this->_inputUnit) {
            $unit = core\time\Duration::getUnitString($this->_inputUnit);
            $output = new aura\html\Element('label', [$output, ' ', $unit,
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
                $this->_inputUnit = core\time\Duration::normalizeUnitId($innerValue['unit']);
            }

            $innerValue = $innerValue->getValue();
        }

        if(is_string($innerValue) && !strlen($innerValue)) {
            $innerValue = null;
        }
        
        if($innerValue !== null) {
            $canOptimize = $this->_unitSelectable && $value instanceof core\IValueContainer;
            $innerValue = $this->_normalizeDurationString($innerValue, $this->_inputUnit, $canOptimize);

            if($canOptimize) {
                $value->setValue($this->_inputUnit);
            }
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

    public function setMin($min) {
        return parent::setMin($this->_normalizeDurationString($min, $this->_defaultInputUnit));
    }
    
    public function setMax($max) {
        return parent::setMax($this->_normalizeDurationString($max, $this->_defaultInputUnit));
    }

    protected function _normalizeDurationString($duration, $unit=null, $canOptimize=false) {
        if($unit === null) {
            $unit = $this->_inputUnit;
        }

        $duration = core\time\Duration::fromUnit($duration, $unit);

        if($duration->isEmpty()) {
            return null;
        }

        $output = $duration->toUnit($this->_inputUnit);

        if($canOptimize) {
            while($output < 1 && $this->_inputUnit > 1) {
                $this->_inputUnit--;
                $output = $duration->toUnit($this->_inputUnit);
            }

            while($output > 60 && $this->_inputUnit < 7) {
                $this->_inputUnit++;
                $output = $duration->toUnit($this->_inputUnit);
            }
        }

        return $output;
    }
}