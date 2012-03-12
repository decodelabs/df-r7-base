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


trait TWidget {
    
    protected $_primaryTag;
    
    public function getTag() {
        if(!$this->_primaryTag) {
            if(!static::PRIMARY_TAG) {
                throw new Exception(
                    'Primary tag name has not been declared for '.$this->getWidgetName().' widget type'
                );
            }
            
            $this->_primaryTag = new aura\html\Tag(static::PRIMARY_TAG);
            $this->_primaryTag->setClasses(array('widget', 'widget-'.lcfirst($this->getWidgetName())));
        }
        
        return $this->_primaryTag;
    }
    
    public function renderTo(aura\view\IRenderTarget $target) {
        $this->setRenderTarget($target);
        return $this->render();
    }
    
    public function toString() {
        return (string)$this->render();
    }
    
    protected function _getRenderTargetDisplayName() {
        if($this->_renderTarget) {
            return get_class($this->_renderTarget);
        } else {
            return null;
        }
    }
}


trait TBodyContentAwareWidget {
    
    protected $_body;
    
    public function withBody() {
        return new aura\html\widget\util\ElementContentWrapper($this, $this->_body);
    }
    
    public function setBody($body) {
        if(!$body instanceof aura\html\IElementContent) {
            $body = new aura\html\ElementContent($body);
        }
        
        $this->_body = $body;
        return $this;
    }
    
    public function getBody() {
        return $this->_body;
    }
}


trait TDisableableWidget {
    
    protected $_isDisabled = false;
    
    public function isDisabled($flag=null) {
        if($flag !== null) {
            $this->_isDisabled = (bool)$flag;
            return $this;
        }
        
        return $this->_isDisabled;
    }
}

trait TAccessControlledWidget {
    
    protected $_checkAccess = false;
    protected $_accessLocks = array();
    
    public function shouldCheckAccess($flag=null) {
        if($flag !== null) {
            $this->_checkAccess = (bool)$flag;
            return $this;
        }
        
        return $this->_checkAccess;
    }
    
    public function addAccessLock(user\IAccessLock $lock) {
        $this->_accessLocks[] = $lock;
        return $this;
    }
    
    public function getAccessLocks() {
        return $this->_accessLocks;
    }
    
    public function clearAccessLocks() {
        $this->_accessLocks = array();
        return $this;
    }
}


// Forms
trait TFormDataWidget {
    
    //const PRIMARY_TAG = 'input';
    //const ARRAY_INPUT = false;
    
    protected $_name;
    protected $_value;
    protected $_inputId;
    protected $_targetFormId;
    
    public function __construct($name, $value=null) {
        $this->setName($name);
        $this->setValue($value);
    }
    
    protected function _applyFormDataAttributes(aura\html\ITag $tag) {
        // Name
        if($this->_name == null) {
            return;
        }
        
        $name = $this->_name;
        
        if(static::ARRAY_INPUT && substr($name, -2) != '[]') {
            $name .= '[]';
        }
        
        $tag->setAttribute('name', $name);
        
        
        // Value
        $tag->setAttribute('value', $this->getValueString());
        
        if($this->_value->hasErrors()) {
            $this->getTag()->addClass('state-error');
        }
        
        
        // Target form
        if($this->_targetFormId) {
            $tag->setAttribute('form', $this->_targetFormId);
        }
    }
    
    
// Name
    public function setName($name) {
        $this->_name = $name;
        return $this;
    }
    
    public function getName() {
        return $this->_name;
    }
    
    
// Value
    public function setValue($value) {
        if(!$value instanceof core\collection\IInputTree) {
            if($value instanceof core\IValueContainer) {
                $value = $value->getValue();
            }
            
            if($value !== null) {
                $value = (string)$value;
            }
            
            $value = new core\collection\InputTree($value);
        }
        
        $this->_value = $value;
        return $this;
    }
    
    public function getValue() {
        if(!$this->_value) {
            $this->_value = new core\collection\InputTree();
        }
        
        return $this->_value;
    }
    
    public function getValueString() {
        return (string)$this->getValue();
    }
    
    
    
// Target form
    public function setTargetFormId($id) {
        $this->_targetFormId = $id;
        return $this;
    }
    
    public function getTargetFormId() {
        return $this->_targetFormId;
    }
}


trait TInputWidget {
    
    use TDisableableWidget;
    
    protected $_isRequired = false;
    protected $_isReadOnly = false;
    protected $_tabIndex;
    
    protected function _applyInputAttributes(aura\html\ITag $tag) {
        $tag->addClass('widget-formInput');
        
        // Required
        if($this->_isRequired) {
            $tag->setAttribute('required', 'required');
            //$tag->addClass('constraint-required');
        }
        
        
        // Disabled 
        if($this->_isDisabled) {
            $tag->setAttribute('disabled', 'disabled');
            //$tag->addClass('state-disabled');
        }
        
        
        // Read only
        if($this->_isReadOnly) {
            $tag->setAttribute('readonly', 'readonly');
            //$tag->addClass('state-readOnly');
        }
        
        
        // Tab index
        if(is_numeric($this->_tabIndex)) {
            $tag->setAttribute('tabindex', (int)$this->_tabIndex);
        } else if($this->_tabIndex === false) {
            $tag->setAttribute('tabindex', '-1');
        }
    }
    
    
    public function isRequired($flag=null) {
        if($flag !== null) {
            $this->_isRequired = (bool)$flag;
            return $this;
        }
        
        return $this->_isRequired;
    }
    
    
    public function isReadOnly($flag=null) {
        if($flag !== null) {
            $this->_isReadOnly = (bool)$flag;
            return $this;
        }
        
        return $this->_isReadOnly;
    }
    
    
    public function setTabIndex($index) {
        $this->_tabIndex = $index;
        return $this;
    }
    
    public function getTabIndex() {
        return $this->_index;
    }
}


trait TFocusableInputWidget {
    
    protected $_shouldAutoFocus = false;
    
    public function shouldAutoFocus($flag=null) {
        if($flag !== null) {
            $this->_shouldAutoFocus = (bool)$flag;
            return $this;
        }
        
        return $this->_shouldAutoFocus;
    }
    
    protected function _applyFocusableInputAttributes(aura\html\ITag $tag) {
        if($this->_shouldAutoFocus) {
            $tag->setAttribute('autofocus', 'autofocus');
        }
    }
}


trait TVisualInputWidget {
    
    use TFocusableInputWidget;
    
    protected $_shouldValidate = true;
    protected $_shouldAutoComplete = null;
    
    public function shouldValidate($flag=null) {
        if($flag !== null) {
            $this->_shouldValidate = (bool)$flag;
            return $this;
        }    
        
        return $this->_shouldValidate;
    }
    
    public function shouldAutoComplete($flag=null) {
        if($flag !== null) {
            $this->_shouldAutoComplete = (bool)$flag;
            return $this;
        }
        
        return (bool)$this->_shouldAutoComplete;
    }
    
    
    protected function _applyVisualInputAttributes(aura\html\ITag $tag) {
        $this->_applyFocusableInputAttributes($tag);
        
        if(!$this->_shouldValidate) {
            $tag->setAttribute('novalidate', 'novalidate');
        }
        
        if($this->_shouldAutoComplete !== null) {
            $tag->setAttribute('autocomplete', $this->_shouldAutoComplete ? 'on' : 'off');
        }
    }
}


trait TOptionalMultipleValueInputWidget {
    
    protected $_allowMultiple = false;
    
    public function allowMultiple($flag=null) {
        if($flag !== null) {
            $this->_allowMultiple = (bool)$flag;
            return $this;
        }
        
        return $this->_allowMultiple;
    }
    
    protected function _applyOptionalMultipleValueInputAttributes(aura\html\ITag $tag) {
        if($this->_allowMultiple) {
            $tag->setAttribute('multiple', 'multiple');
        }
    }
}


trait TDataListEntryWidget {
    
    protected $_dataListId;
    
    public function setDataListId($id) {
        $this->_dataListId = $id;
        return $this;
    }
    
    public function getDataListId() {
        return $this->_dataListId;
    }
    
    protected function _applyDataListEntryAttributes(aura\html\ITag $tag) {
        if($this->_dataListId !== null) {
            $tag->setAttribute('list', $this->_dataListId);
        }
    }
}


trait TTextEntryWidget {
    
    protected $_maxLength;
    protected $_placeholder;
    
    public function setMaxLength($length) {
        $this->_maxLength = $length;
        return $this;
    }
    
    public function getMaxLength() {
        return $this->_maxLength;
    }
    
    
    public function setPlaceholder($placeholder) {
        $this->_placeholder = $placeholder;
        return $this;
    }
    
    public function getPlaceholder() {
        return $this->_placeholder;
    }
    
    protected function _applyTextEntryAttributes(aura\html\ITag $tag) {
        if($this->_maxLength !== null) {
            $tag->setAttribute('maxlength', (int)$this->_maxLength);
        }
        
        if($this->_placeholder !== null) {
            $tag->setAttribute('placeholder', $this->_placeholder);
        }
    }
}


trait TRangeEntryWidget {

    protected $_min;
    protected $_max;
    protected $_step;
    
// Min
    public function setMin($min) {
        $this->_min = $min;
        return $this;
    }
    
    public function getMin() {
        return $this->_min;
    }
    
    
// Max
    public function setMax($max) {
        $this->_max = $max;
        return $this;
    }

    public function getMax() {
        return $this->_max;
    }
    
    
// Step
    public function setStep($step) {
        $this->_step = $step;
        return $this;
    }
    
    public function getStep() {
        return $this->_step;
    }
    
    
    protected function _applyRangeEntryAttributes(aura\html\ITag $tag) {
        if($this->_min !== null) {
            $tag->setAttribute('min', $this->_min);
        }
        
        if($this->_max !== null) {
            $tag->setAttribute('max', $this->_max);
        }
        
        if($this->_step !== null) {
            $tag->setAttribute('step', $this->_step);
        }
    }
}


trait TCheckInputWidget {
    
    protected $_isChecked = false;
    
    public function isChecked($flag=null) {
        if($flag !== null) {
            if($flag instanceof core\collection\IInputTree) {
                $this->getValue()->addErrors($flag->getErrors());
            }
            
            if($flag instanceof core\IValueContainer) {
                $flag = $flag->getValue() == $this->getValueString();
            }
            
            $this->_isChecked = (bool)$flag;
            return $this;
        }
        
        return $this->_isChecked;
    }
    
    protected function _applyCheckInputAttributes(aura\html\ITag $tag) {
        if($this->_isChecked) {
            $tag->setAttribute('checked', 'checked');
        }
    }
}


trait TSelectionInputWidget {
    
    protected $_optionRenderer;
    
    public function setOptionRenderer(Callable $renderer) {
        $this->_optionRenderer = $renderer;
        return $this;
    }
    
    public function getOptionRenderer() {
        return $this->_optionRenderer;
    }
    
    protected function _renderOption(aura\html\IElement $option, $value, $label) {
        if($optionRenderer = $this->_optionRenderer) {
            $optionRenderer($option, $value, $label);
        } else {
            $option->push($label);
        }
    }
}


trait TUngroupedSelectionInputWidget {
    
    use TSelectionInputWidget;
    
    protected $_options = array();
    
    public function setOptions($options, $labelsAsValues=false) {
        $this->_options = array();
        return $this->addOptions($options, $labelsAsValues);
    }
    
    public function addOptions($options, $labelsAsValues=false) {
        if($options instanceof core\collection\ICollection) {
            $options = $options->toArray();
        }
        
        if(is_array($options)) {
            foreach($options as $value => $label) {
                if($labelsAsValues) {
                    $value = $label;
                }
                
                $this->_options[$value] = $label;
            }
        }
        
        return $this;
    }
    
    public function getOptions() {
        return $this->_options;
    }
    
    public function sortOptions($byLabel=false) {
        if($byLabel) {
            asort($this->_options);
        } else {
            ksort($this->_options);
        }
        
        return $this;
    }
}


trait TGroupedSelectionInputWidget {
    
    use TSelectionInputWidget;
    
    protected $_groupOptions = array();
    protected $_groupNames = array();
    
// Options
    public function setOptions($groupId, $options, $labelsAsValues=false) {
        unset($this->_groupOptions[$groupId]);
        return $this->addOptions($groupId, $options, $labelsAsValues);
    }
    
    public function addOptions($groupId, $options, $labelsAsValues=false) {
        if(!isset($this->_groupOptions[$groupId])) {
            $this->_groupOptions[$groupId] = array();
            $this->_groupNames[$groupId] = $groupId;
        }
        
        if($options instanceof core\collection\ICollection) {
            $options = $options->toArray();
        }
        
        if(is_array($options)) {
            foreach($options as $value => $label) {
                if($labelsAsValues) {
                    $value = $label;
                }
                
                $this->_groupOptions[$groupId][$value] = $label;
            }
        }
        
        return $this;
    }
    
    public function getOptions($groupId) {
        if(!isset($this->_groupOptions[$groupId])) {
            return array();
        }
        
        return $this->_groupOptions[$groupId];
    }
    
    public function sortOptions($groupId, $byLabel=false) {
        if(isset($this->_groupOptions[$groupId])) {
            if($byLabel) {
                asort($this->_groupOptions[$groupId]);
            } else {
                ksort($this->_groupOptions[$groupId]);
            }
        }
        
        return $this;
    }
    
    
// Groups
    public function addGroup($id, $name, $options=null, $labelsAsValues=false) {
        $this->setOptions($id, $options, $labelsAsValues);
        $this->setGroupName($id, $name);
        
        return $this;
    }
    
    public function getGroup($id) {
        if(isset($this->_groupOptions[$id])) {
            return $this->_groupOptions[$id];
        }
        
        return array();
    }
    
    public function removeGroup($id) {
        unset($this->_groupOptions[$id], $this->_groupNames[$id]);
        return $this;
    }
    
    public function getGroups() {
        return $this->_groups;
    }
    
    public function setGroupName($id, $name) {
        $this->_groupNames[$id] = $name;
        return $this;
    }
    
    public function getGroupName($id) {
        if(isset($this->_groupNames[$id])) {
            return $this->_groupNames[$id];
        }
        
        return $id;
    }
}




// Lists
trait TDataDrivenListWidget {
    
    protected $_data;
    
    public function setData($data) {
        $this->_data = $data;
        return $this;
    }
    
    public function getData() {
        return $this->_data;
    }
    
    protected function _isDataIterable() {
        return is_array($this->_data) 
            || $this->_data instanceof \Iterator
            || $this->_data instanceof \IteratorAggregate;
    }
}


trait TLinearListWidget {
    
    protected $_renderer;
    
    public function setRenderer(Callable $renderer=null) {
        $this->_renderer = $renderer;
        return $this;
    }
    
    public function getRenderer() {
        return $this->_renderer;
    }
    
    protected function _renderListItem(IRenderContext $renderContext, $value) {
        if($renderer = $this->_renderer) {
            try {
                $value = $renderer($value, $this->getRenderTarget(), $renderContext);
            } catch(\Exception $e) {
                $value = '<span class="state-error">'.$e->getMessage().'</span>';
            }
        } else {
            if($value instanceof core\IStringProvider) {
                $value = $value->toString();
            }
            
            if($value instanceof core\IArrayProvider) {
                $value = $value->toArray();
            }
            
            if(is_array($value)) {
                $value = new self($value);
                $value->setRenderTarget($this->getRenderTarget());
            }
        }
        
        return $value;
    }
}


trait TMappedListWidget {
    
    protected $_fields = array();
    
    public function setField(IField $field) {
        $this->_fields[$field->getId()] = $field;
        return $this;
    }
    
    public function addField($key, $a=null, $b=null) {
        $name = null;
        $renderer = null;
        
        if(is_callable($a)) {
            $renderer = $a;
            $name = $b;
        } else if(is_callable($b)) {
            $renderer = $b;
            $name = $a;
        }
        
        if($name === null) {
            $name = core\string\Manipulator::formatName($key);
        }
        
        if($renderer === null) {
            $renderer = function($data, $renderTarget, $context) {
                $key = $context->getKey();
                $value = null;
                
                if(is_array($data)) {
                    if(isset($data[$key])) {
                        $value = $data[$key];
                    } else {
                        $value = null;
                    }
                } else if($data instanceof \ArrayAccess) {
                    $value = $data[$key];
                } else if(is_object($data)) {
                    if(method_exists($data, '__get')) {
                        $value = $data->__get($key);
                    } else if(method_exists($data, 'get'.ucfirst($key))) {
                        $value = $data->{'get'.ucfirst($key)}();
                    }
                }
                
                return $value;
            };
        }
        
        $this->_fields[$key] = new aura\html\widget\util\Field($key, $name, $renderer);
        
        return $this;
    }
    
    public function removeField($key) {
        if($key instanceof IField) {
            $key = $key->getKey();
        }
        
        unset($this->_fields[$key]);
        
        return $this;
    }
    
    public function getFields() {
        return $this->_fields;
    }
    
    protected function _generateDefaultFields() {
        foreach($this->_data as $key => $value) {
            $this->addField($key);
        }
        
        $fields = $this->_fields;
        $this->_fields = array();
        
        return $fields;
    }
}
