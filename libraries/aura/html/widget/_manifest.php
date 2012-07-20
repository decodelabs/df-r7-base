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

// Exceptions
interface IException {}
class Exception extends \Exception implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class WidgetNotFoundException extends \RuntimeException implements IException {}


// Interfaces
interface IElementContentWrapper extends aura\view\IDeferredRenderable, \ArrayAccess, \Countable {}

interface IRendererContext {
    public function getWidget();
    public function getKey();
    public function getCounter();
    public function getCellTag();
    public function getRowTag();
    public function iterate($key, aura\html\ITag $cellTag=null, aura\html\ITag $rowTag=null);
}

interface IField {
    public function getKey();
    public function setName($name);
    public function getName();
    public function setRenderer(Callable $renderer);
    public function getRenderer();
}



interface IWidgetShortcutProvider {
    public function __call($method, array $args);
}



interface IWidget extends aura\view\IDeferredRenderable, aura\html\IElementRepresentation, aura\html\ITagDataContainer {
    public function getWidgetName();
    public function getTag();
    public function esc($value);
    
    public function setTitle($title);
    public function getTitle();
}


interface IBodyContentAwareWidget extends IWidget {
    public function withBody();
    public function setBody($body);
    public function getBody();
}


interface IDisableableWidget {
    public function isDisabled($flag=null);
}


interface IAccessControlledWidget {
    public function shouldCheckAccess($flag=null);
    public function setAccessLocks(array $locks);
    public function addAccessLocks(array $locks);
    public function addAccessLock(/*user\IAccessLock */$lock);
    public function getAccessLocks();
    public function clearAccessLocks();
}



interface IContainerWidget extends IWidget, core\collection\IIndexedQueue {}
interface IFormOrientedWidget extends IWidget {}

interface IFormDataWidget extends IFormOrientedWidget {
    public function setName($name);
    public function getName();
    
    public function setValue($value);
    public function getValue();
    public function getValueString();
    
    public function setTargetFormId($id);
    public function getTargetFormId();
}

interface IInputWidget extends IFormDataWidget, IDisableableWidget {
    public function isRequired($flag=null);
    public function isReadOnly($flag=null);
    
    public function setTabIndex($index);
    public function getTabIndex();
}

interface IFocusableInputWidget extends IInputWidget {
    public function shouldAutoFocus($flag=null);
}

interface IVisualInputWidget extends IInputWidget, IFocusableInputWidget {
    public function shouldValidate($flag=null);
    public function shouldAutoComplete($flag=null);
}

interface IOptionalMultipleValueInputWidget extends IInputWidget {
    public function allowMultiple($flag=null);
} 

interface IDataListEntryWidget extends IVisualInputWidget {
    public function setDataListId($id);
    public function getDataListId();
}

interface IDataEntryWidget extends IVisualInputWidget, IDataListEntryWidget {}

interface ITextEntryWidget extends IVisualInputWidget {
    public function setMaxLength($length);
    public function getMaxLength();
    
    public function setPlaceholder($placeholder);
    public function getPlaceholder();
}

interface IRangeEntryWidget extends IDataEntryWidget {
    public function setMin($min);
    public function getMin();
    
    public function setMax($max);
    public function getMax();
    
    public function setStep($max);
    public function getStep();
}

interface ICheckInputWidget extends IInputWidget {
    public function isChecked($flag=null);
}

interface ISelectionInputWidget extends IInputWidget {
    public function setOptionRenderer(Callable $renderer);
    public function getOptionRenderer();
}

interface IMultipleSelectionInputWidget extends ISelectionInputWidget {}

interface IUngroupedSelectionInputWidget extends ISelectionInputWidget {
    public function setOptions($options, $labelsAsValues=false);
    public function addOptions($options, $labelsAsValues=false);
    public function getOptions();
    public function sortOptions($byLabel=false);
}

interface IGroupedSelectionInputWidget extends ISelectionInputWidget {
    public function setOptions($groupId, $options, $labelsAsValues=false);
    public function addOptions($groupId, $options, $labelsAsValues=false);
    public function getOptions($groupId);
    public function sortOptions($groupId, $byLabel=false);
    
    public function addGroup($id, $name, $options=null, $labelsAsValues=false);
    public function getGroup($id);
    public function removeGroup($id);
    public function getGroups();
    public function setGroupName($id, $name);
    public function getGroupName($id);
}


interface IDispositionAwareWidget {
    public function setDisposition($diposition);
    public function getDisposition();
    public function getDispositionString();

    public function isPositive($flag=null);
    public function isNegative($flag=null);
}

/*************
 * Actual
 */


// Forms
interface IFormWidget extends IFormOrientedWidget {
    public function setAction($action);
    public function getAction();
    
    public function setMethod($method);
    public function getMethod();
    
    public function setEncoding($encoding);
    public function getEncoding();
    
    public function setName($name);
    public function getName();
    
    public function setTarget($target);
    public function getTarget();
    
    public function shouldAutoComplete($flag=null);
    public function shouldValidate($flag=null);
    
    public function setAcceptCharset($charset);
    public function getAcceptCharset();
}

interface IFieldSetWidget extends IFormOrientedWidget {
    public function withLegendBody();
    public function setLegendBody(aura\html\IElementContent $body);
    public function getLegendBody();
    
    public function setName($name);
    public function getName();
    
    public function setTargetFormId($id);
    public function getTargetFormId();
}


interface IButtonWidget extends IInputWidget, IBodyContentAwareWidget, IDispositionAwareWidget {
    public function shouldValidate($flag=null);
}

interface ITextboxWidget extends ITextEntryWidget, IDataListEntryWidget {
    public function setPattern($pattern);
    public function getPattern();
    
    public function setFormEvent($event);
    public function getFormEvent();
}

interface ITextareaWidget extends ITextEntryWidget {
    public function setColumns($columns);
    public function getColumns();
    
    public function setRows($rows);
    public function getRows();
    
    public function setDirectionInputName($name);
    public function getDirectionInputName();
    
    public function setWrap($wrap);
    public function getWrap();
}

interface IFileUploadWidget extends IFocusableInputWidget, IOptionalMultipleValueInputWidget {
    public function setAcceptTypes($types=null);
    public function getAcceptTypes();
}

interface IDateWidget extends IRangeEntryWidget {}

interface IMultiSelectWidget extends IMultipleSelectionInputWidget {
    public function setSize($size);
    public function getSize();
}


interface ILabelWidget extends IFormOrientedWidget, IBodyContentAwareWidget {
    public function setInputId($inputId);
    public function getInputId();
}

interface IFieldAreaWidget extends IFormOrientedWidget {
    public function withLabelBody();
    public function setLabelBody(aura\html\IElementContent $labelBody);
    public function getLabelBody();
}



// Lists
interface IListWidget extends IWidget {
    
}

interface IDataDrivenListWidget extends IListWidget {
    public function setData($data);
    public function getData();
}

interface ILinearListWidget extends IListWidget {
    public function setRenderer(Callable $renderer=null);
    public function getRenderer();
}

interface IMappedListWidget extends IListWidget {
    public function setField(IField $field);
    public function addField($key, $a=null, $b=null);
    public function removeField($key);
    public function getFields();
}


// Template
interface ITemplateWidget extends IWidget, aura\view\IContentProvider {
    public function setPath($path);
    public function getPath();
    
    public function setContextRequest($contextRequest);
    public function getContextRequest();
}



// Links
interface ILinkWidget extends IWidget, IBodyContentAwareWidget, IDisableableWidget {
    public function setUri($uri, $setAsMatchRequest=false);
    public function getUri();
    
    public function setMatchRequest($request);
    public function getMatchRequest();
    
    public function setTarget($target);
    public function getTarget();
    
    public function setRelationship($rel);
    public function addRelationship($rel);
    public function getRelationship();
    public function removeRelationship($rel);
    
    public function isActive($flag=null);
    
    public function setHrefLanguage($language);
    public function getHrefLanguage();
    
    public function setMedia($media);
    public function getMedia();
    
    public function setContentType($type);
    public function getContentType();
}