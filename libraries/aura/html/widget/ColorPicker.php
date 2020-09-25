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
use df\neon;

use DecodeLabs\Glitch\Dumpable;

class ColorPicker extends Base implements IDataEntryWidget, Dumpable
{
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_FocusableInput;
    use TWidget_VisualInput;
    use TWidget_DataListEntry;

    const PRIMARY_TAG = 'input.color.picker';
    const ARRAY_INPUT = false;

    public function __construct(arch\IContext $context, $name, $value=null)
    {
        parent::__construct($context);

        $this->setName($name);
        $this->setValue($value);
    }

    protected function _render()
    {
        $tag = $this->getTag();

        $tag->setAttribute('type', 'color');
        $this->_applyFormDataAttributes($tag, false);
        $this->_applyInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);
        $this->_applyVisualInputAttributes($tag);
        $this->_applyDataListEntryAttributes($tag);

        $value = $this->getValueString();

        if (!empty($value)) {
            try {
                $value = neon\Color::factory($value)->toHexString();
            } catch (\Throwable $e) {
            }
        }

        $tag->setAttribute('value', $value);

        return $tag;
    }
}
