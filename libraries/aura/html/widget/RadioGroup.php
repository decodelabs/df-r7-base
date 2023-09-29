<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\aura\html\widget;

use DecodeLabs\Glitch\Dumpable;
use df\arch;

use df\aura;

class RadioGroup extends Base implements IUngroupedSelectionInputWidget, Dumpable
{
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_UngroupedSelectionInput;

    public const PRIMARY_TAG = 'div.group.radio';
    public const INPUT_TYPE = 'radio';
    public const ARRAY_INPUT = false;
    public const WIDGET_CLASS = 'w.check.radio';
    public const EMPTY_PLACEHOLDER = '_%_empty_%_';

    protected $_inputIdCounter = 0;
    protected $_shouldWrapBody = true;
    protected $_labelClass = null;
    protected $_emptyLabel = null;

    public function __construct(arch\IContext $context, $name, $value = null, $options = null, $labelsAsValues = false)
    {
        parent::__construct($context);

        $this->setName($name);
        $this->setValue($value);

        if ($options !== null) {
            $this->addOptions($options, $labelsAsValues);
        }
    }

    protected function _render()
    {
        $tag = $this->getTag();
        $optionList = new aura\html\ElementContent();
        $selectionFound = false;
        $isRadio = static::INPUT_TYPE == 'radio';

        $id = $tag->getId();
        $options = $this->_options;

        if ($isRadio) {
            if ($this->_emptyLabel !== null && !$this->_isRequired) {
                $options[static::EMPTY_PLACEHOLDER] = $this->_emptyLabel;
            }

            $currValue = $this->getValue()->getValue();

            if (!strlen((string)$currValue) && $currValue !== false) {
                $this->getValue()->setValue(null);
            }
        }

        foreach ($options as $value => $label) {
            $labelTag = new aura\html\Element('label.' . static::WIDGET_CLASS);

            if ($this->_labelClass) {
                $labelTag->addClass($this->_labelClass);
            }

            $inputTag = new aura\html\Tag('input.' . static::WIDGET_CLASS, [
                'type' => static::INPUT_TYPE
            ]);

            $this->_applyFormDataAttributes($inputTag);
            $this->_applyInputAttributes($inputTag);

            if ($value === static::EMPTY_PLACEHOLDER || !strlen((string)$value)) {
                $value = null;
            }

            $inputTag->setAttribute('value', $value ?? '');
            $inputId = null;

            if ($id !== null) {
                $inputId = $id . '-' . $this->_inputIdCounter++;
                $labelTag->setAttribute('for', $inputId);
                $inputTag->setId($inputId);
            }

            if (!$selectionFound && $this->_checkSelected($value, $selectionFound)) {
                $inputTag->setAttribute('checked', 'checked');
            }

            if ($optionRenderer = $this->_optionRenderer) {
                $optionRenderer($labelTag, $value, $label);
            } else {
                if ($this->_shouldWrapBody) {
                    $label = new aura\html\Element('span', $label);
                }

                $labelTag->push($label);
            }

            $labelTag->unshift($inputTag->render(), ' ');
            $optionList->push($labelTag->render());
        }

        return $tag->renderWith($optionList, true);
    }

    protected function _checkSelected($value, &$selectionFound)
    {
        $currValue = $this->getValue()->getValue();

        if ($currValue === null) {
            return $selectionFound = $value === null;
        }

        return $selectionFound = $value == $currValue;
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

    public function isInline(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag) {
                $this->getTag()->addClass('inline');
            } else {
                $this->getTag()->removeClass('inline');
            }

            return $this;
        } else {
            return $this->getTag()->hasClass('inline');
        }
    }

    public function setEmptyLabel($label)
    {
        $this->_emptyLabel = $label;
        return $this;
    }

    public function getEmptyLabel()
    {
        return $this->_emptyLabel;
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
