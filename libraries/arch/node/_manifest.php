<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\node;

use df;
use df\core;
use df\aura;
use df\arch;
use df\flex;
use df\flow;
use df\halo;
use df\link;
use df\mesh;
use df\opal;
use df\user;

use DecodeLabs\Systemic\Process\Result as ProcessResult;

##############################
## MAIN
##############################
interface INode extends core\IContextAware, user\IAccessLock, arch\IResponseForcer, arch\IOptionalDirectoryAccessLock
{
    public function setCallback($callback);
    public function getCallback(): ?callable;
    public function dispatch();

    public function shouldOptimize(bool $flag=null);
    public function getDispatchMethodName(): ?string;
    public function handleException(\Throwable $e);

    public function getSitemapEntries(): iterable;
}



##############################
## TASKS
##############################
interface ITaskNode extends INode
{
    public static function getSchedule(): ?string;
    public static function getSchedulePriority(): string;
    public static function shouldScheduleAutomatically(): bool;

    public function extractCliArguments(core\cli\ICommand $command);
    public function runChild($request, bool $announce=true);

    public function ensureDfSource();
}

interface IBuildTaskNode extends ITaskNode
{
}


interface ITaskManager extends core\IManager
{
    public function launch($request, core\io\IMultiplexer $multiplexer=null, $user=null, bool $dfSource=false): ProcessResult;
    public function launchBackground($request, $user=null, bool $dfSource=false);
    public function launchQuietly($request);
    public function invoke($request, core\io\IMultiplexer $io=null): core\io\IMultiplexer;
    public function initiateStream($request): link\http\IResponse;
    public function queue($request, string $priority='medium'): flex\IGuid;
    public function queueAndLaunch($request, core\io\IMultiplexer $multiplexer=null): ProcessResult;
    public function queueAndLaunchBackground($request);
}



##############################
## REST API
##############################
interface IRestApiNode extends INode
{
    public function authorizeRequest();
}

interface IRestApiResult extends arch\IProxyResponse
{
    public function isValid(): bool;

    public function setStatusCode(?int $code);
    public function getStatusCode(): int;

    public function setException(\Throwable $e);
    public function hasException(): bool;
    public function getException(): ?\Throwable;

    public function complete(callable $success, callable $failure=null);

    public function setDataProcessor(?callable $processor);
    public function getDataProcessor(): ?callable;

    public function setCors(?string $cors);
    public function getCors(): ?string;
}



##############################
## FORMS
##############################
interface IStoreProvider
{
    public function setStore($key, $value);
    public function hasStore(...$keys): bool;
    public function getStore($key, $default=null);
    public function removeStore(...$keys);
    public function clearStore();
}

interface IFormState extends IStoreProvider
{
    public function getSessionId(): string;
    public function getValues(): core\collection\IInputTree;

    public function getDelegateState(string $id): IFormState;

    public function isNew(bool $flag=null);
    public function reset();
    public function isOperating(): bool;
}

interface IFormEventDescriptor
{
    public static function factory($output): IFormEventDescriptor;

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

interface IForm extends IStoreProvider, core\lang\IChainable, \ArrayAccess
{
    public function isRenderingInline(): bool;
    public function getState(): IFormState;
    public function reloadDefaultValues(): void;
    public function loadDelegate(string $id, string $path): IDelegate;
    public function directLoadDelegate(string $id, string $class): IDelegate;
    public function proxyLoadDelegate(string $id, IDelegateProxy $proxy): IDelegate;
    public function getDelegate(string $id): IDelegate;
    public function hasDelegate(string $id): bool;
    public function unloadDelegate(string $id);

    public function isValid(): bool;
    public function countErrors(): int;
    public function fieldName(string $name): string;
    public function eventName(string $name, string ...$args): string;
    public function elementId(string $name): string;
}

interface IActiveForm extends IForm
{
    public function isNew(): bool;

    public function handleEvent(string $name, array $args=[]): IFormEventDescriptor;
    public function handleDelegateEvent(string $delegateId, string $event, array $args);
    public function triggerPostEvent(IActiveForm $target, string $event, array $args);
    public function handlePostEvent(IActiveForm $target, string $event, array $args);
    public function handleMissingDelegate(string $id, string $event, array $args): bool;

    public function getAvailableEvents(): array;
    public function getStateData(): array;

    public function reset();
    public function complete($success=true, $failure=null);
    public function isComplete(): bool;
}


interface IFormNode extends INode, IActiveForm
{
    public function dispatchToRenderInline(aura\view\IView $view);
    public function setComplete();
}

interface IWizard extends IFormNode
{
    public function getCurrentSection(): string;
    public function setSection(string $section);
    public function getPrevSection(): ?string;
    public function getNextSection(): ?string;
    public function getSectionData(string $section=null): core\collection\ITree;
}


interface IDelegate extends IActiveForm, core\IContextAware
{
    public function getDelegateId(): string;
    public function getDelegateKey(): string;

    public function initialize();
    public function beginInitialize();
    public function endInitialize();

    public function setRenderContext(aura\view\IView $view, aura\view\IContentProvider $content, $isRenderingInline=false);
    public function setComplete();
}

interface IModalDelegate
{
    public function getAvailableModes(): array;
    public function setDefaultMode(?string $mode);
    public function getDefaultMode(): ?string;
}

interface IInlineFieldRenderableDelegate
{
    public function renderField($label=null);
    public function renderInlineFieldContent();
    public function renderFieldContent(aura\html\widget\Field $field);
}

interface ISelfContainedRenderableDelegate
{
    public function renderFieldSet($legend=null);
    public function renderContainer();
    public function renderContainerContent(aura\html\widget\IContainerWidget $fieldSet);
}

interface IParentEventHandlerDelegate extends IDelegate
{
    public function apply();
}

interface IParentUiHandlerDelegate extends IDelegate
{
    public function renderUi();
}

interface IResultProviderDelegate extends core\constraint\IRequirable, IParentEventHandlerDelegate
{
}

interface ISelectionProviderDelegate extends IResultProviderDelegate
{
    public function isForOne(bool $flag=null);
    public function isForMany(bool $flag=null);
}

interface ISelectorDelegate extends ISelectionProviderDelegate, IDependencyValueProvider
{
    public function getSourceEntityLocator(): mesh\entity\ILocator;
    public function isSelected(?string $id): bool;
    public function setSelected($selected);
    public function getSelected();
    public function hasSelection(): bool;
    public function removeSelected(?string $id);
    public function clearSelection();
}

interface IInlineFieldRenderableSelectorDelegate extends IInlineFieldRenderableDelegate, ISelectorDelegate
{
}
interface IInlineFieldRenderableModalSelectorDelegate extends IModalDelegate, IInlineFieldRenderableDelegate, ISelectorDelegate
{
}
interface IAdapterDelegate extends IParentUiHandlerDelegate, IParentEventHandlerDelegate
{
}

interface IDependencyValueProvider
{
    public function getDependencyValue();
    public function hasDependencyValue(): bool;
}

interface IDependentDelegate extends opal\query\IFilterConsumer
{
    public function addDependency($value, string $message=null, callable $filter=null, callable $callback=null);
    public function setDependency(string $name, $value, string $message=null, callable $filter=null, callable $callback=null);
    public function hasDependency(string $name): bool;
    public function getDependency(string $name): ?array;
    public function removeDependency(string $name);
    public function getDependencies(): array;
    public function getDependencyMessages(): array;
    public function normalizeDependencyValues();
}


interface IDelegateProxy
{
    public function loadFormDelegate(arch\IContext $context, IFormState $state, IFormEventDescriptor $event, string $id): IDelegate;
}
