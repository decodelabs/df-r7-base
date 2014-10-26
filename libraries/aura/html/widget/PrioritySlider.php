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

class PrioritySlider extends Base implements IInputWidget {
    
    use TWidget_FormData;
    use TWidget_Input;

    const PRIMARY_TAG = 'div';

    public function __construct(arch\IContext $context, $name, $value=null) {
        $this->setName($name);
        $this->setValue($value);
    }

    protected function _normalizeValue(core\collection\IInputTree $value) {
        $inner = $value->getValue();

        if($inner === null) {
            return;
        }

        if(!is_numeric($inner)) {
            switch($inner) {
                case 'critical': $inner = 4; break;
                case 'high': $inner = 3; break;
                case 'medium': $inner = 2; break;
                case 'low': $inner = 1; break;
                case 'trivial': $inner = 0; break;
                default: $inner = 2; break;
            }
        }

        if($inner < 0) {
            $inner = 0;
        }

        if($inner > 4) {
            $inner = 4;
        }

        $value->setValue($inner);
    }

    protected function _render() {
        $tag = $this->getTag();

        return $tag->renderWith([
            new aura\html\Element('span', 'Trivial'),
            ' ',
            new aura\html\Element('input', null, [
                'name' => $this->getName(),
                'value' => $this->getValue()->getValue(),
                'type' => 'range',
                'min' => 0,
                'max' => 4,
                'step' => 1,
                'required' => $this->_isRequired,
                'class' => 'widget-rangeSlider'
            ]),
            ' ',
            new aura\html\Element('span', 'Critical')
        ]);
    }
}