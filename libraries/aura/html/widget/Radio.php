<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use DecodeLabs\Glitch\Dumpable;
use df\arch;

use df\aura;

class Radio extends Base implements ICheckInputWidget, Dumpable
{
    use TWidget_BodyContentAware;
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_FocusableInput;
    use TWidget_CheckInput;

    public const PRIMARY_TAG = 'input.check.radio';
    public const ARRAY_INPUT = false;
    public const INPUT_TYPE = 'radio';

    protected $_shouldWrapBody = true;
    protected $_labelClass = null;

    public function __construct(arch\IContext $context, $name, $isChecked = false, $body = null, $value = '1')
    {
        parent::__construct($context);

        $this->setName($name);
        $this->setValue($value);
        $this->isChecked($isChecked);
        $this->setBody($body);
    }

    protected function _render()
    {
        $tag = $this->getTag();

        $tag->setAttribute('type', static::INPUT_TYPE);

        $this->_applyFormDataAttributes($tag);
        $this->_applyInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);
        $this->_applyCheckInputAttributes($tag);

        $output = $tag;
        $widgetClass = 'w.' . static::INPUT_TYPE;

        if (!$this->_body->isEmpty()) {
            $label = $this->_body;

            if ($this->_shouldWrapBody) {
                $label = new aura\html\Element('span', $label);
            }

            $output = new aura\html\Element('label.' . $widgetClass, $label);

            if ($this->_isDisabled) {
                $output->addClass('disabled');
            }

            if ($this->_labelClass) {
                $output->addClass($this->_labelClass);
            }

            $output->unshift($tag, ' ');
        }

        return $output;
    }

    public function shouldWrapBody(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_shouldWrapBody = $flag;
            return $this;
        }

        return $this->_shouldWrapBody;
    }

    public function setLabelClass($class)
    {
        $this->_labelClass = $class;
        return $this;
    }

    public function getLabelClass()
    {
        return $this->_labelClass;
    }
}
