<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\node;

use DecodeLabs\Fluidity\Cast;
use DecodeLabs\Tagged\Markup;
use DecodeLabs\Terminus\Session;
use df\arch;
use df\arch\node\form\State as FormState;
use df\aura;
use df\aura\html\widget\Field as FieldWidget;

use df\aura\html\widget\FieldSet as FieldSetWidget;
use df\aura\html\widget\IContainerWidget;
use df\aura\view\IContentProvider as ViewContentProvider;
use df\core;
use df\flex;
use df\link;
use df\mesh;

use df\opal;
use df\opal\record\IPartial;
use df\opal\record\IRecord;
use df\user;

use Stringable;

##############################
## MAIN
##############################
interface INode extends core\IContextAware, user\IAccessLock, arch\IResponseForcer, arch\IOptionalDirectoryAccessLock
{
    public function setCallback($callback);
    public function getCallback(): ?callable;
    public function dispatch();

    public function shouldOptimize(bool $flag = null);
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

    public function prepareArguments(): array;

    public function execute(): void;
    public function runChild($request, bool $announce = true);
    public function ensureDfSource();
}

interface IBuildTaskNode extends ITaskNode
{
}


interface ITaskManager extends core\IManager
{
    public function launch($request, ?Session $session = null, $user = null, bool $dfSource = false, bool $decoratable = null): bool;
    public function launchBackground($request, $user = null, bool $dfSource = false, bool $decoratable = null);
    public function launchQuietly($request): void;
    public function invoke($request): void;
    public function initiateStream($request): link\http\IResponse;
    public function queue($request, string $priority = 'medium'): flex\IGuid;
    public function queueAndLaunch($request, ?Session $session = null): bool;
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

    public function complete(callable $success, callable $failure = null);

    public function setDataProcessor(?callable $processor);
    public function getDataProcessor(): ?callable;

    public function setCors(?string $cors);
    public function getCors(): ?string;

    public function setAccessToken(?string $token);
    public function hasAccessToken(): bool;
    public function getAccessToken(): ?string;
    public function setRefreshToken(?string $token);
    public function hasRefreshToken(): bool;
    public function getRefreshToken(): ?string;
}



##############################
## FORMS
##############################
interface IStoreProvider
{
    /**
     * @return $this
     */
    public function setStore(
        string $key,
        mixed $value
    ): static;

    public function hasStore(string ...$keys): bool;

    public function getStore(
        string $key,
        mixed $default = null
    ): mixed;

    /**
     * @return $this
     */
    public function removeStore(string ...$keys): static;

    /**
     * @return $this
     */
    public function clearStore(): static;
}

interface IFormState extends IStoreProvider
{
    public function getSessionId(): string;
    public function getValues(): core\collection\IInputTree;

    public function getDelegateState(string $id): FormState;

    public function isNew(bool $flag = null);

    /**
     * @return $this
     */
    public function reset(): static;
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
    public function shouldForceRedirect(bool $flag = null);
    public function shouldReload(bool $flag = null);

    public function setResponse($response);
    public function getResponse();
    public function hasResponse(): bool;
}

interface IForm extends
    IStoreProvider,
    core\lang\IChainable,
    \ArrayAccess
{
    public function isRenderingInline(): bool;
    public function getState(): FormState;
    public function reloadDefaultValues(): void;
    public function loadDelegate(string $id, string $path): IDelegate;
    public function directLoadDelegate(string $id, string $class): IDelegate;
    public function proxyLoadDelegate(string $id, IDelegateProxy $proxy): IDelegate;
    public function getDelegate(string $id): IDelegate;
    public function hasDelegate(string $id): bool;

    /**
     * @return $this
     */
    public function unloadDelegate(string $id): static;

    public function isValid(): bool;
    public function countErrors(): int;
    public function fieldName(string $name): string;
    public function eventName(string $name, string ...$args): string;
    public function elementId(string $name): string;
}

interface IActiveForm extends IForm
{
    public function isNew(): bool;

    public function handleEvent(string $name, array $args = []): IFormEventDescriptor;

    public function handleDelegateEvent(
        string $delegateId,
        string $event,
        array $args
    ): void;

    public function triggerPostEvent(
        IActiveForm $target,
        string $event,
        array $args
    ): void;

    public function handlePostEvent(
        IActiveForm $target,
        string $event,
        array $args
    ): void;

    public function handleMissingDelegate(string $id, string $event, array $args): bool;

    public function getAvailableEvents(): array;
    public function getStateData(): array;

    /**
     * @return $this
     */
    public function reset(): static;

    public function complete(
        bool|object|array|string $success = true,
        ?callable $failure = null
    ): mixed;

    public function isComplete(): bool;
}


interface IFormNode extends INode, IActiveForm
{
    public function dispatchToRenderInline(aura\view\IView $view): ViewContentProvider;
    public function setComplete(): void;
}

interface IWizard extends IFormNode
{
    public function getCurrentSection(): string;
    public function setSection(string $section);
    public function getPrevSection(): ?string;
    public function getNextSection(): ?string;
    public function getSectionData(string $section = null): core\collection\ITree;
}


interface IDelegate extends
    IActiveForm,
    core\IContextAware,
    Cast
{
    public function getDelegateId(): string;
    public function getDelegateKey(): string;

    public function initialize();
    public function beginInitialize();
    public function endInitialize();

    /**
     * @return $this
     */
    public function setRenderContext(
        aura\view\IView $view,
        aura\view\content\WidgetContentProvider $content,
        bool $isRenderingInline = false
    ): static;

    public function setComplete(): void;
}

interface IModalDelegate
{
    public function getAvailableModes(): array;

    /**
     * @return $this
     */
    public function setDefaultMode(?string $mode): static;
    public function getDefaultMode(): ?string;
}

interface IInlineFieldRenderableDelegate
{
    public function renderField(mixed $label = null): FieldWidget;
    public function renderInlineFieldContent(): Markup;
    public function renderFieldContent(FieldWidget $field): void;
}

interface ISelfContainedRenderableDelegate
{
    public function renderFieldSet(mixed $legend = null): FieldSetWidget;
    public function renderContainer(): Markup;
    public function renderContainerContent(IContainerWidget $fieldSet);
}

interface IParentEventHandlerDelegate extends IDelegate
{
    public function apply(): mixed;
}

interface IParentUiHandlerDelegate extends IDelegate
{
    public function renderUi(): void;
}

interface IResultProviderDelegate extends core\constraint\IRequirable, IParentEventHandlerDelegate
{
}

interface ISelectionProviderDelegate extends IResultProviderDelegate
{
    public function isForOne(bool $flag = null);
    public function isForMany(bool $flag = null);
}

interface ISelectorDelegate extends ISelectionProviderDelegate, IDependencyValueProvider
{
    public function getSourceEntityLocator(): mesh\entity\ILocator;
    public function isSelected(?string $id): bool;

    /**
     * @return $this
     */
    public function setSelected(
        string|Stringable|int|IRecord|IPartial|array|null $selected
    ): static;

    public function getSelected(): string|array|null;
    public function hasSelection(): bool;

    /**
     * @return $this
     */
    public function removeSelected(?string $id): static;

    /**
     * @return $this
     */
    public function clearSelection(): static;
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
    public function getDependencyValue(): mixed;
    public function hasDependencyValue(): bool;
}

interface IDependentDelegate extends opal\query\IFilterConsumer
{
    /**
     * @return $this
     */
    public function addDependency(
        mixed $value,
        ?string $message = null,
        ?callable $filter = null,
        ?callable $callback = null
    ): static;

    /**
     * @return $this
     */
    public function setDependency(
        string $name,
        mixed $value,
        ?string $message = null,
        ?callable $filter = null,
        ?callable $callback = null
    ): static;

    public function hasDependency(string $name): bool;
    public function getDependency(string $name): ?array;
    public function removeDependency(string $name): void;
    public function getDependencies(): array;
    public function getDependencyMessages(): array;
    public function normalizeDependencyValues(): void;
}


interface IDelegateProxy
{
    public function loadFormDelegate(
        arch\IContext $context,
        FormState $state,
        IFormEventDescriptor $event,
        string $id
    ): IDelegate;
}
