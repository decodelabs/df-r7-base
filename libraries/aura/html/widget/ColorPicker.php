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

class ColorPicker extends Base implements IDataEntryWidget, core\IDumpable {
    
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_FocusableInput;
    use TWidget_VisualInput;
    use TWidget_DataListEntry;
    
    const PRIMARY_TAG = 'input';
    const ARRAY_INPUT = false;
    
    public function __construct(arch\IContext $context, $name, $value=null) {
        $this->setName($name);
        $this->setValue($value);
    }

    protected function _render() {
        $tag = $this->getTag();
        
        $tag->setAttribute('type', 'color');
        $this->_applyFormDataAttributes($tag, false);
        $this->_applyInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);
        $this->_applyVisualInputAttributes($tag);
        $this->_applyDataListEntryAttributes($tag);

        $value = neon\Color::factory($this->getValueString());
        $tag->setAttribute('value', $value->toHexString());
        
        return $tag;
    }
}
