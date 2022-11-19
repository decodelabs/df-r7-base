<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

class EmailTextbox extends Textbox implements IOptionalMultipleValueInputWidget
{
    use TWidget_OptionalMultipleValueInput;

    public const PRIMARY_TAG = 'input.textbox.email';
    public const INPUT_TYPE = 'email';

    protected function _render()
    {
        $this->_applyOptionalMultipleValueInputAttributes($this->getTag());

        return parent::_render();
    }
}
