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

class NumberTextbox extends Base implements IRangeEntryWidget, core\IDumpable {

    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_VisualInput;
    use TWidget_FocusableInput;
    use TWidget_DataListEntry;
    use TWidget_PlaceholderProvider;
    use TWidget_RangeEntry;

    const PRIMARY_TAG = 'input.textbox.number';
    const ARRAY_INPUT = false;
    const INPUT_TYPE = 'number';
    const DEFAULT_PLACEHOLDER = null;

    public function __construct(arch\IContext $context, $name, $value=null) {
        parent::__construct($context);

        $this->setName($name);
        $this->setValue($value);
    }

    protected function _render() {
        $tag = $this->getTag();
        $tag->setAttribute('type', $this->_getInputType());

        $this->_applyFormDataAttributes($tag);
        $this->_applyInputAttributes($tag);
        $this->_applyVisualInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);
        $this->_applyDataListEntryAttributes($tag);
        $this->_applyPlaceholderAttributes($tag);
        $this->_applyRangeEntryAttributes($tag);

        return $tag;
    }

    protected function _getInputType() {
        return static::INPUT_TYPE;
    }

    protected function _normalizeValue(core\collection\IInputTree $value) {
        $number = $value->getValue();

        if($number !== null) {
            $number = number_format(str_replace(',', '', $number), 2, '.', '');
        }

        $value->setValue($number);
    }


// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->_name,
            'value' => $this->_value,
            'min' => $this->_min,
            'max' => $this->_max,
            'step' => $this->_step,
            'tag' => $this->getTag()
        ];
    }
}
