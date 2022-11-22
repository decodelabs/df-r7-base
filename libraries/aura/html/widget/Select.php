<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use DecodeLabs\Glitch\Dumpable;
use df\arch;

use df\aura;

class Select extends Base implements
    IUngroupedSelectionInputWidget,
    IFocusableInputWidget,
    Dumpable
{
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_VisualInput;
    use TWidget_FocusableInput;
    use TWidget_UngroupedSelectionInput;

    public const PRIMARY_TAG = 'select.single';
    public const ARRAY_INPUT = false;

    protected $_markSelected = true;
    protected $_noSelectionLabel = '--';

    public function __construct(arch\IContext $context, $name, $value = null, $options = null, $labelsAsValues = false)
    {
        parent::__construct($context);

        $this->setName($name);
        $this->setValue($value);

        if ($options !== null) {
            $this->addOptions($options, $labelsAsValues);
        }
    }

    public function shouldMarkSelected(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_markSelected = $flag;
            return $this;
        }

        return $this->_markSelected;
    }

    public function setNoSelectionLabel($label)
    {
        $this->_noSelectionLabel = $label;
        return $this;
    }

    public function getNoSelectionLabel()
    {
        return $this->_noSelectionLabel;
    }

    protected function _render()
    {
        $tag = $this->getTag();

        $this->_applyFormDataAttributes($tag, false);
        $this->_applyInputAttributes($tag);
        $this->_applyVisualInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);

        $optionList = new aura\html\ElementContent();
        $selectionFound = false;

        foreach ($this->_options as $value => $label) {
            $isSelected = !$selectionFound && $this->_checkSelected($value, $selectionFound);
            $option = new aura\html\Element('option', null, ['value' => $value]);

            if ($isSelected) {
                $option->setAttribute('selected', 'selected');
            }

            if ($optionRenderer = $this->_optionRenderer) {
                $optionRenderer($option, $value, $label);
            } else {
                $option->push($label);
            }

            if ($isSelected && $this->_markSelected) {
                $option->unshift('» '); // @ignore-non-ascii
            }

            $optionList->push($option->render());
        }

        if (!$this->isRequired() && !$tag->hasAttribute('multiple')) {
            $optionList->unshift(new aura\html\Element('option', $this->_noSelectionLabel !== '--' ? $this->_noSelectionLabel : '', ['value' => '']));
        } elseif (!$selectionFound && $this->_noSelectionLabel !== null) {
            $optionList->unshift(new aura\html\Element('option', $this->_noSelectionLabel, ['value' => '', 'disabled' => true, 'selected' => true]));
        }

        return $tag->renderWith($optionList, true);
    }

    protected function _checkSelected($value, &$selectionFound)
    {
        return $selectionFound = (string)$value === $this->getValueString();
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            '*name' => $this->_name,
            '*value' => $this->_value,
            '%tag' => $this->getTag()
        ];

        yield 'values' => $this->_options;
    }
}
