<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form;

use df;
use df\core;
use df\arch;
use df\aura;
use df\opal;

// Exceptions
interface IException extends arch\IException {}
class LogicException extends \LogicException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class DelegateException extends RuntimeException {}
class EventException extends RuntimeException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface IStoreProvider {
    public function setStore($key, $value);
    public function hasStore($key);
    public function getStore($key, $default=null);
    public function removeStore($key);
    public function clearStore();
}

interface IStateController extends IStoreProvider {
    public function getSessionId();
    public function getValues();
    
    public function getDelegateState($id);
    
    public function isNew($flag=null);
    public function reset();
    public function isOperating();
}

interface IForm extends IStoreProvider, core\lang\IChainable {
    public function isRenderingInline();
    public function getStateController();
    public function loadDelegate($id, $path);
    public function directLoadDelegate($id, $class);
    public function getDelegate($id);
    public function hasDelegate($id);
    public function unloadDelegate($id);

    public function isValid();
    public function fieldName($name);
    public function eventName($name);
    public function elementId($name);
}

interface IActiveForm extends IForm {
    public function isNew();

    public function handleEvent($name, array $args=[]);
    public function handleDelegateEvent($delegateId, $event, $args);

    public function getAvailableEvents();
    public function getStateData();

    public function reset();
    public function complete($success=true, $failure=null);
    public function isComplete();
}


interface IAction extends arch\IAction, IActiveForm {
    public function setComplete();
}

interface IWizard extends IAction {
    public function getCurrentSection();
    public function setSection($section);
    public function getPrevSection();
    public function getNextSection();
    public function getSectionData($section=null);
}


interface IDelegate extends IActiveForm, core\IContextAware {
    public function getDelegateId();
    public function getDelegateKey();
    public function initialize();
    public function setRenderContext(aura\view\IView $view, aura\view\IContentProvider $content, $isRenderingInline=false);
    public function setComplete($success=true);
}


interface IModalDelegate {
    public function getAvailableModes();
    public function setDefaultMode($mode);
    public function getDefaultMode();
}

interface IRenderableDelegate {
    public function isStacked($flag=null);
}

interface IInlineFieldRenderableDelegate extends IRenderableDelegate {
    public function renderFieldArea($label=null);
    public function renderInlineFieldAreaContent();
    public function renderFieldAreaContent(aura\html\widget\FieldArea $fieldArea);
}

interface ISelfContainedRenderableDelegate extends IRenderableDelegate {
    public function renderFieldSet($legend=null);
    public function renderContainer();
    public function renderContainerContent(aura\html\widget\IContainerWidget $fieldSet);
}

interface IRequirableDelegate {
    public function isRequired($flag=null);
}

interface IParentEventHandlerDelegate extends IDelegate {
    public function apply();
}

interface IParentUiHandlerDelegate extends IDelegate {
    public function renderUi();
}

interface IResultProviderDelegate extends IRequirableDelegate, IParentEventHandlerDelegate {}

interface ISelectionProviderDelegate extends IResultProviderDelegate {
    public function isForOne($flag=null);
    public function isForMany($flag=null);
}

interface ISelectorDelegate extends ISelectionProviderDelegate, IDependencyValueProvider {
    public function getSourceEntityLocator();
    public function isSelected($id);
    public function setSelected($selected);
    public function getSelected();
    public function hasSelection();
    public function removeSelected($id);
    public function clearSelection();
}

interface IInlineFieldRenderableSelectorDelegate extends IInlineFieldRenderableDelegate, ISelectorDelegate {}
interface IInlineFieldRenderableModalSelectorDelegate extends IModalDelegate, IInlineFieldRenderableDelegate, ISelectorDelegate {}

interface IAdapterDelegate extends IParentUiHandlerDelegate, IParentEventHandlerDelegate {
    
}


interface IDependencyValueProvider {
    public function getDependencyValue();
    public function hasDependencyValue();
}

interface IDependentDelegate extends opal\query\IFilterConsumer {
    public function addDependency($value, $message=null, $filter=null);
    public function setDependency($name, $value, $message=null, $filter=null);
    public function hasDependency($name);
    public function getDependency($name);
    public function removeDependency($name);
    public function getDependencies();
    public function getDependencyMessages();
}