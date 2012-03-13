<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class GroupedSelectList extends Base implements IGroupedSelectionInputWidget, IFocusableInputWidget, core\IDumpable {
    
    use TWidget_FormData;
    use TWidget_Input;
    use TWidget_VisualInput;
    use TWidget_FocusableInput;
    use TWidget_GroupedSelectionInput;
    
    const PRIMARY_TAG = 'select';
    const ARRAY_INPUT = false;
    
    protected $_selected;
    
    public function __construct($name, $value=null) {
        $this->setName($name);
        $this->setValue($value);
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
            $optGroup = new aura\html\Element('optgroup', null, array('label' => $this->getGroupName($groupId)));
            
            foreach($group as $value => $label) {
                $option = new aura\html\Element('option', null, array('value' => $value));
                
                if(!$selectionFound && $this->_checkSelected($value, $selectionFound)) {
                    $option->setAttribute('selected', 'selected');
                }
                
                if($optionRenderer = $this->_optionRenderer) {
                    $optionRenderer($option, $value, $label);
                } else {
                    $option->push($label);
                }
                
                $optGroup->push($option->render());
            }
            
            $groupList->push($optGroup->render(true));
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
            'tag' => $this->getTag(),
            'renderTarget' => $this->_getRenderTargetDisplayName()
        ];
    }
}
