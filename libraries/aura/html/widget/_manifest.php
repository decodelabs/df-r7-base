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
use df\user;

// Exceptions
interface IException {}
class Exception extends \Exception implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class WidgetNotFoundException extends \RuntimeException implements IException {}


// Interfaces
interface IElementContentWrapper extends \ArrayAccess, \Countable {}

interface IRendererContext extends core\collection\IMappedCollection {
    public function setComponent(arch\IComponent $component);
    public function getComponent();
    public function getWidget();
    public function getKey();
    public function getCounter();
    public function getCellTag();
    public function getRowTag();
    public function addDivider();
    public function setDivider($label);
    public function getDivider();
    public function prepareRow($row);
    public function reset();
    public function iterate($key, aura\html\ITag $cellTag=null, aura\html\ITag $rowTag=null);
    public function iterateField($field, aura\html\ITag $cellTag=null, aura\html\ITag $rowTag=null);
    public function renderCell($value, $renderer=null);
    public function skipRow();
    public function shouldSkipRow();
}

interface IField {
    public function getKey();
    public function setName($name);
    public function getName();

    public function addLabel($key, $label=null);
    public function removeLabel($key);
    public function getLabels();
    public function getHeaderList();

    public function setRenderer($renderer);
    public function getRenderer();
    public function render($data, aura\html\widget\IRendererContext $renderContext);
}



interface IWidgetShortcutProvider {
    public function __call($method, array $args);
}



interface IWidget extends aura\html\IElementRepresentation, aura\html\ITagDataContainer, core\lang\IChainable {
    public function getWidgetName();
    public function getTag();
    public function esc($value);
    public function isInline();
    public function isBlock();
}

interface IWidgetProxy {
    public function toWidget();
}


interface IBodyContentAwareWidget extends IWidget {
    public function withBody();
    public function setBody($body);
    public function getBody();
    public function hasBody();
}


interface IDisableableWidget {
    public function isDisabled(bool $flag=null);
}

interface IContainerWidget extends IWidget, core\collection\IIndexedQueue, aura\html\IWidgetFinder {}


interface IFormOrientedWidget extends IWidget {}

interface IFormDataWidget extends IFormOrientedWidget {
    public function setName($name);
    public function getName();

    public function setValue($value);
    public function getValue();
    public function getValueString();
    public function replaceValue($value);

    public function setTargetFormId($id);
    public function getTargetFormId();
}

interface IInputWidget extends IFormDataWidget, IDisableableWidget {
    public function isRequired(bool $flag=null);
    public function isReadOnly(bool $flag=null);

    public function setTabIndex($index);
    public function getTabIndex();
}

interface IFocusableInputWidget extends IInputWidget {
    public function shouldAutoFocus(bool $flag=null);
}

interface IVisualInputWidget extends IInputWidget, IFocusableInputWidget {
    public function shouldValidate(bool $flag=null);
    public function shouldAutoComplete(bool $flag=null);
}

interface IOptionalMultipleValueInputWidget extends IInputWidget {
    public function allowMultiple(bool $flag=null);
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

    public function shouldSpellCheck(bool $flag=null);
}

interface IRangeEntryWidget extends IDataEntryWidget {
    public function setRange($min, $max, $step=null);

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
    public function setOptionRenderer($renderer);
    public function getOptionRenderer();
}

interface IMultipleSelectionInputWidget extends ISelectionInputWidget {}

interface IUngroupedOptionWidget extends IFormOrientedWidget {
    public function setOptions($options, $labelsAsValues=false);
    public function addOptions($options, $labelsAsValues=false);
    public function getOptions();
    public function sortOptions($byLabel=false);
}

interface IUngroupedSelectionInputWidget extends IUngroupedOptionWidget, ISelectionInputWidget {}

interface IGroupedSelectionInputWidget extends ISelectionInputWidget {
    public function setOptions($options, $labelsAsValues=false);
    public function addOptions($options, $labelsAsValues=false);
    public function getOptions();
    public function sortOptions($byLabel=false);

    public function setGroupOptions($groupId, $options, $labelsAsValues=false);
    public function addGroupOptions($groupId, $options, $labelsAsValues=false);
    public function getGroupOptions($groupId);
    public function sortGroupOptions($groupId, $byLabel=false);

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
}


interface IIconProviderWidget {
    public function setIcon(string $icon=null);
    public function getIcon();
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

    public function shouldAutoComplete(bool $flag=null);
    public function shouldValidate(bool $flag=null);

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
    public function shouldValidate(bool $flag=null);
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

interface IFileUploadWidget extends IFocusableInputWidget, IOptionalMultipleValueInputWidget, core\io\IAcceptTypeProcessor {}

interface IDateWidget extends IRangeEntryWidget {}

interface IMultiSelectWidget extends IMultipleSelectionInputWidget {
    public function setSize($size);
    public function getSize();
}


interface ILabelWidget extends IFormOrientedWidget, IBodyContentAwareWidget {
    public function setInputId($inputId);
    public function getInputId();
}

interface IFieldWidget extends IFormOrientedWidget {
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

interface IOrderedDataDrivenListWidget extends IDataDrivenListWidget {
    public function setStartIndex($start);
    public function getStartIndex();
    public function isReversed(bool $flag=null);
}

interface ILinearListWidget extends IListWidget {
    public function setRenderer($renderer=null);
    public function getRenderer();
}

interface IMappedListWidget extends IListWidget {
    public function setRowProcessor($processor=null);
    public function getRowProcessor();
    public function setField(IField $field);
    public function addField($key, $a=null, $b=null);
    public function addFieldAtIndex($index, $key, $a=null, $b=null);
    public function removeField($key);
    public function addLabel($fieldKey, $labelKey, $label=null);
    public function removeLabel($fieldKey, $labelKey);
    public function getFields();
    public function getField($key);
}




// Links
interface ILinkWidget extends IWidget, IBodyContentAwareWidget, IDisableableWidget, IDispositionAwareWidget, arch\navigation\ILink {
    public function setTarget($target);
    public function getTarget();

    public function setRelationship(...$rel);
    public function addRelationship(...$rel);
    public function getRelationship();
    public function removeRelationship(...$rel);

    public function isActive(bool $flag=null);

    public function setHrefLanguage($language);
    public function getHrefLanguage();

    public function setMedia($media);
    public function getMedia();

    public function setContentType($type);
    public function getContentType();
}

interface IDescriptionAwareLinkWidget extends ILinkWidget {
    public function setDescription($description);
    public function getDescription();
    public function shouldShowDescription(bool $flag=null);
}