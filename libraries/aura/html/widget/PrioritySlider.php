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

    const PRIMARY_TAG = 'div.range';

    public function __construct(arch\IContext $context, $name, $value=null) {
        parent::__construct($context);

        $this->setName($name);
        $this->setValue($value);
    }

    protected function _normalizeValue(core\collection\IInputTree $value) {
        $inner = $value->getValue();

        if($inner === null) {
            return;
        }

        $inner = core\unit\Priority::factory($inner);
        $value->setValue($inner);
    }

    protected function _render() {
        $tag = $this->getTag();
        $value = $this->getValue()->getValue();

        if($value !== null) {
            $value = $value->getIndex();
        }

        return $tag->renderWith([
            new aura\html\Element('span', 'Trivial'),
            ' ',
            new aura\html\Element('input', null, [
                'name' => $this->getName(),
                'value' => $value,
                'type' => 'range',
                'min' => 0,
                'max' => 4,
                'step' => 1,
                'required' => $this->_isRequired,
                'class' => 'w.number.slider'
            ]),
            ' ',
            new aura\html\Element('span', 'Critical')
        ]);
    }
}
