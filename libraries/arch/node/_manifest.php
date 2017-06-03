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
use df\link;


##############################
## MAIN
##############################
interface INode extends core\IContextAware, user\IAccessLock, arch\IResponseForcer, arch\IOptionalDirectoryAccessLock {
    public function setCallback($callback);
    public function getCallback();
    public function dispatch();

    public function shouldOptimize(bool $flag=null);
    public function getDispatchMethodName(): ?string;
    public function handleException(\Throwable $e);

    public function getSitemapEntries();
}



##############################
## TASKS
##############################
interface ITaskNode extends INode {
    public static function getSchedule();
    public static function getSchedulePriority();
    public static function shouldScheduleAutomatically();

    public function extractCliArguments(core\cli\ICommand $command);
    public function runChild($request, $incLevel=true);
    public function runChildQuietly($request);

    public function ensureDfSource();
}

interface IBuildTaskNode extends ITaskNode {}


interface ITaskManager extends core\IManager {
    public function launch($request, core\io\IMultiplexer $multiplexer=null, $user=null, $dfSource=false);
    public function launchBackground($request, $user=null, $dfSource=false);
    public function launchQuietly($request);
    public function invoke($request, core\io\IMultiplexer $io=null);
    public function initiateStream($request);
    public function queue($request, $priority='medium');
    public function queueAndLaunch($request, core\io\IMultiplexer $multiplexer=null);
    public function queueAndLaunchBackground($request);
    public function getSharedIo();
    public function shouldCaptureBackgroundTasks(bool $flag=null);
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

    public function setException(\Throwable $e);
    public function hasException();
    public function getException();

    public function complete(callable $success, callable $failure=null);

    public function setDataProcessor(?callable $processor);
    public function getDataProcessor(): ?callable;

    public function setCors(?string $cors);
    public function getCors(): ?string;
}



##############################
## FORMS
##############################
interface IStoreProvider {
    public function setStore($key, $value);
    public function hasStore(...$keys): bool;
    public function getStore($key, $default=null);
    public function removeStore(...$keys);
    public function clearStore();
}

interface IFormState extends IStoreProvider {
    public function getSessionId();
    public function getValues();

    public function getDelegateState($id);

    public function isNew(bool $flag=null);
    public function reset();
    public function isOperating();
}

interface IFormEventDescriptor {
    public function parseOutput($output);

    public function setTarget(/*string?*/ $target);
    public function getTarget();

    public function setEventName(string $name);
    public function getEventName();
    public function getFullEventName();
    public function getFullEventCall();
    public function setEventArgs(array $args);
    public function getEventArgs();

    public function setSuccessCallback($callback);
    public function getSuccessCallback();
    public function triggerSuccess(IForm $form);
    public function setFailureCallback($callback);
    public function getFailureCallback();
    public function triggerFailure(IForm $form);

    public function setRedirect($redirect);
    public function getRedirect();
    public function hasRedirect(): bool;
    public function shouldForceRedirect(bool $flag=null);
    public function shouldReload(bool $flag=null);

    public function setResponse($response);
    public function getResponse();
    public function hasResponse(): bool;
}

interface IForm extends IStoreProvider, core\lang\IChainable, \ArrayAccess {
    public function isRenderingInline(): bool;
    public function getState();
    public function loadDelegate($id, $path);
    public function directLoadDelegate($id, $class);
    public function getDelegate($id);
    public function hasDelegate($id);
    public function unloadDelegate($id);

    public function isValid();
    public function countErrors();
    public function fieldName($name);
    public function eventName($name, ...$args);
    public function elementId($name);
}

interface IActiveForm extends IForm {
    public function isNew();

    public function handleEvent($name, array $args=[]);
    public function handleDelegateEvent($delegateId, $event, $args);
    public function triggerPostEvent(IActiveForm $target, string $event, array $args);
    public function handlePostEvent(IActiveForm $target, string $event, array $args);
    public function handleMissingDelegate(string $id, string $event, array $args): bool;

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
    public function setComplete();
}

interface IModalDelegate {
    public function getAvailableModes();
    public function setDefaultMode($mode);
    public function getDefaultMode();
}

interface IInlineFieldRenderableDelegate {
    public function renderField($label=null);
    public function renderInlineFieldContent();
    public function renderFieldContent(aura\html\widget\Field $field);
}

interface ISelfContainedRenderableDelegate {
    public function renderFieldSet($legend=null);
    public function renderContainer();
    public function renderContainerContent(aura\html\widget\IContainerWidget $fieldSet);
}

interface IParentEventHandlerDelegate extends IDelegate {
    public function apply();
}

interface IParentUiHandlerDelegate extends IDelegate {
    public function renderUi();
}

interface IResultProviderDelegate extends core\constraint\IRequirable, IParentEventHandlerDelegate {}

interface ISelectionProviderDelegate extends IResultProviderDelegate {
    public function isForOne(bool $flag=null);
    public function isForMany(bool $flag=null);
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
    public function addDependency($value, $message=null, $filter=null, $callback=null);
    public function setDependency($name, $value, $message=null, $filter=null, $callback=null);
    public function hasDependency($name);
    public function getDependency($name);
    public function removeDependency($name);
    public function getDependencies();
    public function getDependencyMessages();
    public function normalizeDependencyValues();
}
