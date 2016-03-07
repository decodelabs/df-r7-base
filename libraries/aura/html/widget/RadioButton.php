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

class RadioButton extends Base implements ICheckInputWidget, core\IDumpable {

    use TWidget_BodyContentAware;
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_FocusableInput;
    use TWidget_CheckInput;

    const PRIMARY_TAG = 'input';
    const ARRAY_INPUT = false;
    const INPUT_TYPE = 'radio';

    protected $_shouldWrapBody = true;
    protected $_labelClass = null;

    public function __construct(arch\IContext $context, $name, $isChecked=false, $body=null, $value='1') {
        parent::__construct($context);

        $this->setName($name);
        $this->setValue($value);
        $this->isChecked($isChecked);
        $this->setBody($body);
    }

    protected function _render() {
        $tag = $this->getTag();

        $tag->setAttribute('type', static::INPUT_TYPE);

        $this->_applyFormDataAttributes($tag);
        $this->_applyInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);
        $this->_applyCheckInputAttributes($tag);

        $output = $tag;

        switch(static::INPUT_TYPE) {
            case 'radio':
                $widgetClass = 'w-radioButton';
                break;

            default:
                $widgetClass = 'w-'.static::INPUT_TYPE;
                break;
        }

        if(!$this->_body->isEmpty()) {
            $label = $this->_body;

            if($this->_shouldWrapBody) {
                $label = new aura\html\Element('span', $label);
            }

            $output = new aura\html\Element('label.'.$widgetClass.'Label', $label);

            if($this->_isDisabled) {
                $output->addClass('disabled');
            }

            if($this->_labelClass) {
                $output->addClass($this->_labelClass);
            }

            $output->unshift($tag, ' ');
        }

        return $output;
    }

    public function shouldWrapBody(bool $flag=null) {
        if($flag !== null) {
            $this->_shouldWrapBody = $flag;
            return $this;
        }

        return $this->_shouldWrapBody;
    }

    public function setLabelClass($class) {
        $this->_labelClass = $class;
        return $this;
    }

    public function getLabelClass() {
        return $this->_labelClass;
    }
}
