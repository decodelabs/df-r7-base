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

class GroupedSelectList extends Base implements IGroupedSelectionInputWidget, IFocusableInputWidget, core\IDumpable {

    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_VisualInput;
    use TWidget_FocusableInput;
    use TWidget_GroupedSelectionInput;

    const PRIMARY_TAG = 'select';
    const ARRAY_INPUT = false;

    protected $_selected;
    protected $_markSelected = true;
    protected $_noSelectionLabel = '--';

    public function __construct(arch\IContext $context, $name, $value=null, $options=null, $labelsAsValues=false) {
        parent::__construct($context);

        $this->setName($name);
        $this->setValue($value);
        $this->setOptions($options, $labelsAsValues);
    }

    public function shouldMarkSelected(bool $flag=null) {
        if($flag !== null) {
            $this->_markSelected = $flag;
            return $this;
        }

        return $this->_markSelected;
    }

    public function setNoSelectionLabel($label) {
        $this->_noSelectionLabel = $label;
        return $this;
    }

    public function getNoSelectionLabel() {
        return $this->_noSelectionLabel;
    }

    protected function _render() {
        $tag = $this->getTag();

        $this->_applyFormDataAttributes($tag, false);
        $this->_applyInputAttributes($tag);
        $this->_applyVisualInputAttributes($tag);
        $this->_applyFocusableInputAttributes($tag);

        $groupList = new aura\html\ElementContent();
        $selectionFound = false;

        foreach($this->_groupOptions as $groupId => $group) {
            $optGroup = new aura\html\Element('optgroup', null, ['label' => $this->getGroupName($groupId)]);

            foreach($group as $value => $label) {
                $isSelected = !$selectionFound && $this->_checkSelected($value, $selectionFound);
                $option = new aura\html\Element('option', null, ['value' => $value]);

                if($isSelected) {
                    $option->setAttribute('selected', 'selected');
                }

                if($optionRenderer = $this->_optionRenderer) {
                    $optionRenderer($option, $value, $label);
                } else {
                    $option->push($label);
                }

                if($isSelected && $this->_markSelected) {
                    $option->unshift('» ');
                }

                $optGroup->push($option->render());
            }

            $groupList->push($optGroup->render(true));
        }

        if(!$selectionFound && $this->_noSelectionLabel !== null) {
            $groupList->unshift(new aura\html\Element('option', $this->_noSelectionLabel, ['value' => null, 'disabled' => true, 'selected' => true]));
        }

        return $tag->renderWith($groupList, true);
    }

    protected function _checkSelected($value, &$selectionFound) {
        return $selectionFound = $value == $this->getValueString();
    }


// Dump
    public function getDumpProperties() {
        return [
            'name' => $this->_name,
            'value' => $this->_value,
            'options' => $this->_groupOptions,
            'groupNames' => $this->_groupNames,
            'tag' => $this->getTag()
        ];
    }
}
