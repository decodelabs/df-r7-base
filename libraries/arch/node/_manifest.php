<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\node;

use df;
use df\core;
use df\arch;
use df\user;
use df\aura;
use df\flow;
use df\opal;


// Exceptions
interface IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}


class DelegateException extends RuntimeException {}
class EventException extends RuntimeException {}



##############################
## MAIN
##############################
interface INode extends core\IContextAware, user\IAccessLock, arch\IResponseForcer, arch\IOptionalDirectoryAccessLock {
    public function setCallback($callback);
    public function getCallback();
    public function dispatch();
    public function getController();
    public function shouldOptimize($flag=null);
    public function getDispatchMethodName();
    public function handleException(\Exception $e);
}



##############################
## TASKS
##############################
interface ITaskNode extends INode {
    public static function getSchedule();
    public static function getScheduleEnvironmentMode();
    public static function getSchedulePriority();
    public static function shouldScheduleAutomatically();

    public function extractCliArguments(core\cli\ICommand $command);
    public function runChild($request, $incLevel=true);
    public function runChildQuietly($request);

    public function ensureEnvironmentMode($mode);
    public function promptEnvironmentMode($mode, $default=false);
}


interface ITaskManager extends core\IManager {
    public function launch($request, core\io\IMultiplexer $multiplexer=null, $environmentMode=null, $user=null);
    public function launchBackground($request, $environmentMode=null, $user=null);
    public function launchQuietly($request);
    public function invoke($request, core\io\IMultiplexer $io=null);
    public function initiateStream($request, $environmentMode=null);
    public function queue($request, $priority='medium', $environmentMode=null);
    public function queueAndLaunch($request, core\io\IMultiplexer $multiplexer=null, $environmentMode=null);
    public function queueAndLaunchBackground($request, $environmentMode=null);
    public function getSharedIo();
    public function shouldCaptureBackgroundTasks($flag=null);
}



##############################
## REST API
##############################
interface IRestApiNode extends INode {
    public function authorizeRequest();
}

interface IRestApiResult extends arch\IProxyResponse {
    public function isValid();

    public function setStatusCode($code);
    public function getStatusCode();

    public function setException(\Exception $e);
    public function hasException();
    public function getException();
}



##############################
## FORMS
##############################
interface IStoreProvider {
    public function setStore($key, $value);
    public function hasStore($key);
    public function getStore($key, $default=null);
    public function removeStore($key);
    public function clearStore();
}

interface IFormState extends IStoreProvider {
    public function getSessionId();
    public function getValues();

    public function getDelegateState($id);

    public function isNew($flag=null);
    public function reset();
    public function isOperating();
}

interface IForm extends IStoreProvider, core\lang\IChainable, \ArrayAccess {
    public function isRenderingInline();
    public function getState();
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


interface IFormNode extends INode, IActiveForm {
    public function setComplete();
}

interface IWizard extends IFormNode {
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
    public function beginInitialize();
    public function endInitialize();
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
    public function renderField($label=null);
    public function renderInlineFieldContent();
    public function renderFieldContent(aura\html\widget\Field $field);
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
interface IAdapterDelegate extends IParentUiHandlerDelegate, IParentEventHandlerDelegate {}

interface IDependencyValueProvider {
    public function getDependencyValue();
    public function hasDependencyValue();
}

interface IDependentDelegate extends opal\query\IFilterConsumer {
    public function addDependency($value, $message=null, $filter=null);
    public function setDependency($name, $value, $message=null, $filter=null, $callback=null);
    public function hasDependency($name);
    public function getDependency($name);
    public function removeDependency($name);
    public function getDependencies();
    public function getDependencyMessages();
    public function normalizeDependencyValues();
}