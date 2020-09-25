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

use DecodeLabs\Glitch\Dumpable;

class Textbox extends Base implements ITextboxWidget, Dumpable
{
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_VisualInput;
    use TWidget_FocusableInput;
    use TWidget_PlaceholderProvider;
    use TWidget_TextEntry;
    use TWidget_DataListEntry;

    const PRIMARY_TAG = 'input.textbox.text';
    const ARRAY_INPUT = false;

    const INPUT_TYPE = 'text';
    const DEFAULT_PLACEHOLDER = null;

    protected $_pattern;
    protected $_formEvent;

    public function __construct(arch\IContext $context, $name, $value=null)
    {
        parent::__construct($context);

        $this->setName($name);
        $this->setValue($value);

        if (static::DEFAULT_PLACEHOLDER !== null) {
            $this->setPlaceholder(static::DEFAULT_PLACEHOLDER);
        }
    }

    protected function _render()
    {
        $tag = $this->getTag();
        $tag->setAttribute('type', $this->_getInputType());

        $this->_applyFormDataAttributes($tag);
        $this->_applyInputAttributes($tag);
        $this->_applyVisualInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);
        $this->_applyPlaceholderAttributes($tag);
        $this->_applyTextEntryAttributes($tag);
        $this->_applyDataListEntryAttributes($tag);


        if ($this->_pattern !== null) {
            $tag->setAttribute('pattern', $this->_pattern);
        }

        if ($this->_formEvent !== null) {
            $tag->setDataAttribute('formevent', $this->_formEvent);
        }

        return $tag;
    }

    protected function _getInputType()
    {
        return static::INPUT_TYPE;
    }


    // Pattern
    public function setPattern($pattern)
    {
        $this->_pattern = $pattern;
        return $this;
    }

    public function getPattern()
    {
        return $this->_pattern;
    }


    // Form event
    public function setFormEvent($event)
    {
        $this->_formEvent = $event;
        return $this;
    }

    public function getFormEvent()
    {
        return $this->_formEvent;
    }
}
