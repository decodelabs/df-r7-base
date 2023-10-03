<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\node;

use DecodeLabs\Exceptional;
use DecodeLabs\Glitch;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Tagged\Markup;
use df\apex\models\media\bucket\Record as BucketRecord;
use df\arch;
use df\arch\node\form\State as FormState;
use df\arch\node\IFormEventDescriptor as EventDescriptor;
use df\arch\scaffold\Loader as ScaffoldLoader;
use df\aura;
use df\aura\html\widget\Field as FieldWidget;
use df\aura\html\widget\FieldSet as FieldSetWidget;
use df\aura\view\content\WidgetContentProvider;
use df\core;
use df\core\collection\IInputTree as InputTree;
use df\link;
use df\link\http\response\Redirect;
use df\neon\bucket\IBucket;
use df\opal;
use df\opal\record\IPartial;
use df\opal\record\IRecord;
use Stringable;

// BASE
trait TForm
{
    use core\lang\TChainable;
    use aura\view\TView_CascadingHelperProvider;

    public WidgetContentProvider $content;
    public InputTree $values;
    public EventDescriptor $event;

    protected bool $_isRenderingInline = false;
    protected ?FormState $_state = null;
    protected array $_delegates = [];

    protected function init(): void
    {
    }
    protected function setDefaultValues(): void
    {
    }

    public function reloadDefaultValues(): void
    {
        if (empty($this->_delegates)) {
            $this->loadDelegates();
        }

        $this->setDefaultValues();

        foreach ($this->_delegates as $delegate) {
            $delegate->reloadDefaultValues();
        }

        $this->_state->isNew(false);
    }

    protected function loadDelegates(): void
    {
    }
    protected function afterInit()
    {
    }

    public function getState(): FormState
    {
        return $this->_state;
    }

    // Delegates
    public function loadDelegate(
        string $id,
        string $name
    ): IDelegate {
        if (false !== strpos($id, '.')) {
            throw Exceptional::InvalidArgument(
                'Delegate IDs must not contain . character'
            );
        }

        $location = $this->context->extractDirectoryLocation($name);
        $context = $this->context->spawnInstance($location);
        $path = $context->location->getController();
        $area = $context->location->getArea();

        if (!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = [];
        }

        $parts[] = '_formDelegates';
        $nameParts = explode('/', $name);
        $topName = (string)array_pop($nameParts);

        if (!empty($nameParts)) {
            $parts = array_merge($parts, $nameParts);
        }

        $parts[] = ucfirst($topName);
        $state = $this->_state->getDelegateState($id);
        $mainId = $this->_getDelegateIdPrefix() . $id;

        $class = 'df\\apex\\directory\\' . $area . '\\' . implode('\\', $parts);

        if (!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\' . implode('\\', $parts);

            if (!class_exists($class)) {
                try {
                    $scaffold = ScaffoldLoader::fromContext($context);
                    return $this->_delegates[$id] = $scaffold->loadFormDelegate($name, $state, $this->event, $mainId);
                } catch (\Throwable $e) {
                    core\logException($e);
                }

                throw Exceptional::{'df/arch/node/Delegate,NotFound'}(
                    'Delegate ' . $name . ' could not be found at ~' . $area . '/' . $path
                );
            }
        }

        return $this->_delegates[$id] = new $class($context, $state, $this->event, $mainId);
    }

    public function directLoadDelegate(string $id, string $class): IDelegate
    {
        if (!class_exists($class)) {
            throw Exceptional::{'df/arch/node/Delegate,NotFound'}(
                'Cannot direct load delegate ' . $id . ' - class not found'
            );
        }

        return $this->_delegates[$id] = new $class(
            $this->context,
            $this->_state->getDelegateState($id),
            $this->event,
            $this->_getDelegateIdPrefix() . $id
        );
    }

    public function proxyLoadDelegate(
        string $id,
        arch\node\IDelegateProxy $proxy
    ): IDelegate {
        return $this->_delegates[$id] = $proxy->loadFormDelegate(
            $this->context,
            $this->_state->getDelegateState($id),
            $this->event,
            $this->_getDelegateIdPrefix() . $id
        );
    }

    public function getDelegate(string $id): IDelegate
    {
        $id = trim($id, ' .');

        if (!strlen($id)) {
            throw Exceptional::{'df/arch/node/Delegate,InvalidArgument'}(
                'Empty delegate id detected'
            );
        }

        $id = explode('.', $id);
        $top = array_shift($id);

        if (!isset($this->_delegates[$top])) {
            throw Exceptional::{'df/arch/node/Delegate,NotFound'}(
                'Delegate ' . $top . ' could not be found'
            );
        }

        $output = $this->_delegates[$top];

        if (!empty($id)) {
            $output = $output->getDelegate(implode('.', $id));
        }

        return $output;
    }

    public function hasDelegate(string $id): bool
    {
        $id = trim($id, ' .');

        if (!strlen($id)) {
            return false;
        }

        $id = explode('.', $id);
        $top = array_shift($id);

        if (!isset($this->_delegates[$top])) {
            return false;
        }

        $delegate = $this->_delegates[$top];

        if (!empty($id)) {
            return $delegate->hasDelegate(implode('.', $id));
        }

        return true;
    }

    public function unloadDelegate(string $id): static
    {
        $id = trim($id, ' .');

        if (!strlen($id)) {
            throw Exceptional::{'df/arch/node/Delegate,InvalidArgument'}(
                'Empty delegate id detected'
            );
        }

        $id = explode('.', $id);
        $top = array_shift($id);

        if (!isset($this->_delegates[$top])) {
            throw Exceptional::{'df/arch/node/Delegate,NotFound'}(
                'Delegate ' . $top . ' could not be found'
            );
        }

        $delegate = $this->_delegates[$top];

        if (!empty($id)) {
            $delegate->unloadDelegate(implode('.', $id));
            return $this;
        }

        $this->_state->clearDelegateState($top);
        unset($this->_delegates[$top]);

        return $this;
    }


    protected function _getDelegateIdPrefix(): string
    {
        if ($this instanceof IDelegate) {
            return $this->_delegateId . '.';
        }

        return '';
    }


    public function isRenderingInline(): bool
    {
        return $this->_isRenderingInline;
    }


    /**
     * @param string $id
     * @param string $name
     */
    public function offsetSet(
        mixed $id,
        mixed $name
    ): void {
        $this->loadDelegate($id, $name);
    }

    /**
     * @param string $id
     * @return IDelegate
     */
    public function offsetGet(mixed $id): mixed
    {
        return $this->getDelegate($id);
    }

    /**
     * @param string $id
     */
    public function offsetExists(mixed $id): bool
    {
        return $this->hasDelegate($id);
    }

    /**
     * @param string $id
     */
    public function offsetUnset(mixed $id): void
    {
        $this->unloadDelegate($id);
    }



    // Store

    /**
     * @return $this
     */
    public function setStore(
        string $key,
        mixed $value
    ): static {
        $this->_state->setStore($key, $value);
        return $this;
    }

    public function hasStore(string ...$keys): bool
    {
        return $this->_state->hasStore(...$keys);
    }

    public function getStore(
        string $key,
        mixed $default = null
    ): mixed {
        return $this->_state->getStore($key, $default);
    }

    /**
     * @return $this
     */
    public function removeStore(string ...$keys): static
    {
        $this->_state->removeStore(...$keys);
        return $this;
    }

    /**
     * @return $this
     */
    public function clearStore(): static
    {
        $this->_state->clearStore();
        return $this;
    }


    // Delivery
    public function isValid(): bool
    {
        if (
            $this->_state &&
            !$this->_state->values->isValid()
        ) {
            return false;
        }

        foreach ($this->_delegates as $delegate) {
            if (!$delegate->isValid()) {
                return false;
            }
        }

        return true;
    }

    public function countErrors(): int
    {
        $output = $this->values->countErrors();

        foreach ($this->_delegates as $delegate) {
            $output += $delegate->countErrors();
        }

        return $output;
    }

    public function complete(
        bool|object|array|string $success = true,
        ?callable $failure = null
    ): mixed {
        $isDirect = is_bool($success);

        if (
            is_callable($success) ||
            core\lang\Util::isAnonymousObject($success) ||
            is_array($success)
        ) {
            $this->event->parseOutput($success);
            $success = true;
        } elseif (
            is_string($success) ||
            $success instanceof arch\IRequest ||
            $success instanceof link\http\IUrl
        ) {
            $this->event->setRedirect($success);
            $success = true;
        }

        if ($failure) {
            $this->event->setFailureCallback($failure);
        }


        if ($this->isValid() || $isDirect) {
            $output = $this->event->triggerSuccess($this);

            if ($output === false) {
                return null;
            } elseif ($output === true) {
                $output = null;
            }

            $this->_isComplete = true;

            if (
                !$this->event->hasRedirect() &&
                !$this->event->hasResponse()
            ) {
                $this->event->setRedirect($this->_getCompleteRedirect());
            }
        } else {
            $this->event->triggerFailure($this);
        }

        return null;
    }

    protected function _getCompleteRedirect(
        ?string $default = null,
        bool $success = true
    ): Redirect {
        return Legacy::$http->defaultRedirect(
            $default,
            $success,
            $this->_state->referrer ?? null,
            $this->getDefaultRedirect()
        );
    }


    // Names
    public function eventName(
        string $name,
        string ...$args
    ): string {
        $output = $this->_getDelegateIdPrefix() . $name;

        if (!empty($args)) {
            foreach ($args as $i => $arg) {
                $args[$i] = '\'' . addslashes($arg) . '\'';
            }

            $output .= '(' . implode(',', $args) . ')';
        }

        return $output;
    }



    // Events
    public function handleEvent(
        string $name,
        array $args = []
    ): EventDescriptor {
        $this->event->setTarget(
            $this instanceof IDelegate ?
                $this->getDelegateId() :
                null
        )
            ->setEventName($name)
            ->setEventArgs($args);

        $func = 'on' . ucfirst($name) . 'Event';

        if (!method_exists($this, $func)) {
            $func = 'onDefaultEvent';

            if (!method_exists($this, $func)) {
                throw Exceptional::{'df/arch/node/Event,Definition'}(
                    'Event ' . $name . ' does not have a handler'
                );
            }
        }

        $this->_beforeEvent($name);
        $output = $this->{$func}(...$args);

        $this->event->parseOutput($output);
        return $this->event;
    }

    protected function _beforeEvent(string $event): void
    {
    }

    protected function onResetEvent(): mixed
    {
        $this->reset();
        return null;
    }

    protected function onRefreshEvent(): mixed
    {
        return null;
    }

    public function handleDelegateEvent(
        string $delegateId,
        string $event,
        array $args
    ): void {
    }

    public function triggerPostEvent(
        IActiveForm $target,
        string $event,
        array $args
    ): void {
        if ($this !== $target) {
            $this->handlePostEvent($target, $event, $args);
        }

        foreach ($this->_delegates as $id => $delegate) {
            $delegate->triggerPostEvent($target, $event, $args);
        }
    }

    public function handlePostEvent(
        IActiveForm $target,
        string $event,
        array $args
    ): void {
    }

    public function handleMissingDelegate(
        string $id,
        string $event,
        array $args
    ): bool {
        return false;
    }


    public function getAvailableEvents(): array
    {
        $output = [];
        $ref = new \ReflectionClass($this);

        foreach ($ref->getMethods() as $method) {
            if (preg_match('/^on([A-Z\_][a-zA-Z0-9_]*)Event$/', (string)$method->getName(), $matches)) {
                $output[] = $this->eventName(lcfirst($matches[1]));
            }
        }

        foreach ($this->_delegates as $delegate) {
            $output = array_merge($output, $delegate->getAvailableEvents());
        }

        return $output;
    }
}




// Parent renderer
trait TForm_ParentUiHandlerDelegate
{
    final public function renderUi(): void
    {
        if ($decorator = arch\decorator\Delegate::factory($this)) {
            $decorator->renderUi();
        } else {
            $this->createUi();
        }
    }

    abstract protected function createUi(): void;
}




// Modal
trait TForm_ModalDelegate
{
    protected $_defaultMode = null;

    public function getAvailableModes(): array
    {
        return array_keys($this->_getModeRenderers());
    }

    protected function _getModeRenderers(): array
    {
        if (defined('static::DEFAULT_MODES')) {
            return static::DEFAULT_MODES;
        }

        return [];
    }

    public function setDefaultMode(?string $mode): static
    {
        if ($mode === null) {
            $this->_defaultMode = null;
            return $this;
        }


        $modes = $this->getAvailableModes();

        if (!in_array($mode, $modes)) {
            throw Exceptional::InvalidArgument(
                'Mode ' . $mode . ' is not recognised in this form'
            );
        }

        $this->_defaultMode = $mode;
        return $this;
    }

    public function getDefaultMode(): ?string
    {
        if (empty($this->_defaultMode)) {
            $this->_defaultMode = $this->_getDefaultMode();
        }

        return $this->_defaultMode;
    }

    abstract protected function _getDefaultMode(): string;

    protected function setMode(string $mode): void
    {
        $this->_state->setStore('mode', $mode);
    }

    protected function getMode(?string $default = null): string
    {
        if ($default === null) {
            $default = $this->getDefaultMode();
        }

        return $this->_state->getStore('mode', $default);
    }

    protected function switchMode(
        string|array $from,
        string $to,
        ?callable $do = null
    ): void {
        if (!is_array($from)) {
            $from = [$from];
        }

        $mode = $this->getMode();

        if (!in_array($mode, $from)) {
            return;
        }

        $this->setMode($to);

        if ($do) {
            core\lang\Callback::factory($do)->invoke($this, $from, $to);
        }
    }

    protected function createModeUi(array $args): void
    {
        $mode = $this->getMode();
        $modes = $this->_getModeRenderers();
        $func = null;

        if (isset($modes[$mode])) {
            $func = $modes[$mode];
        }

        if (!$func || !method_exists($this, $func)) {
            throw Exceptional::{'df/arch/node/Delegate,Definition'}(
                'Selector delegate has no render handler for ' . $mode . ' mode'
            );
        }

        $this->{$func}(...$args);
    }
}


// Inline field renderable
trait TForm_InlineFieldRenderableDelegate
{
    public function renderField(mixed $label = null): FieldWidget
    {
        $this->renderFieldContent(
            $output = $this->html->field($label)
        );

        return $output;
    }

    public function renderInlineFieldContent(): Markup
    {
        return $this->renderField()->renderInputArea();
    }
}



// Self contained renderable
trait TForm_SelfContainedRenderableDelegate
{
    public function renderFieldSet(mixed $legend = null): FieldSetWidget
    {
        $this->renderContainerContent(
            $output = $this->html->fieldSet($legend)
        );

        return $output;
    }

    public function renderContainer(): Markup
    {
        $this->renderContainerContent(
            $output = $this->html->container()
        );

        return $output;
    }
}



// Selector
trait TForm_SelectorDelegate
{
    use core\constraint\TRequirable;

    protected bool $_isForMany = true;

    /**
     * @return ($flag is null ? bool : $this)
     */
    public function isForOne(
        ?bool $flag = null
    ): bool|static {
        if ($flag !== null) {
            $this->_isForMany = !$flag;
            return $this;
        }

        return !$this->_isForMany;
    }

    /**
     * @return ($flag is null ? bool : $this)
     */
    public function isForMany(
        ?bool $flag = null
    ): bool|static {
        if ($flag !== null) {
            $this->_isForMany = $flag;
            return $this;
        }

        return $this->_isForMany;
    }
}



trait TForm_ValueListSelectorDelegate
{
    // Selected
    public function isSelected(?string $id): bool
    {
        if ($id === null) {
            return false;
        }

        if (!$this->_isForMany) {
            return (string)$this->values['selected'] == $id;
        } else {
            return $this->values->selected->has($id);
        }
    }

    /**
     * @return $this
     */
    public function setSelected(
        string|Stringable|int|IRecord|IPartial|array|null $selected
    ): static {
        $this->values->selected->clear();
        $this->addSelected($selected);
        return $this;
    }

    /**
     * @return $this
     */
    public function addSelected(
        string|Stringable|int|IRecord|IPartial|array|null $selected
    ): static {
        if (!$this->_isForMany) {
            $this->values->selected = $this->_normalizeSelection($selected);
        } else {
            if (!is_array($selected)) {
                $selected = [$selected];
            }


            foreach ($selected as $id) {
                $id = $this->_normalizeSelection($id);
                $this->values->selected[$id] = $id;
            }
        }

        return $this;
    }

    protected function _normalizeSelection(
        string|Stringable|int|IRecord|IPartial|array|null $selection
    ): ?string {
        if ($selection === null) {
            return null;
        }

        if ($selection instanceof opal\record\IRecord) {
            $selection = $selection->getPrimaryKeySet();
        } elseif ($selection instanceof opal\record\IPartial) {
            if ($selection->isBridge()) {
                Glitch::incomplete($selection);
            } else {
                $selection = $selection->getPrimaryKeySet();
            }
        }

        return $this->_sanitizeSelection((string)$selection);
    }

    protected function _sanitizeSelection(string $selection): string
    {
        return $selection;
    }

    public function getSelected(): string|array|null
    {
        if (!$this->_isForMany) {
            return $this->values['selected'];
        } else {
            return $this->values->selected->toArray();
        }
    }

    public function hasSelection(): bool
    {
        if (!$this->_isForMany) {
            return $this->values->selected->hasValue();
        } else {
            return !$this->values->selected->isEmpty();
        }
    }

    /**
     * @return $this
     */
    public function clearSelection(): static
    {
        return $this->setSelected(null);
    }

    /**
     * @return $this
     */
    public function removeSelected(?string $id): static
    {
        if ($id !== null) {
            if (!$this->_isForMany) {
                unset($this->values->selected);
            } else {
                unset($this->values->selected->{$id});
            }
        }

        return $this;
    }

    public function getDependencyValue(): mixed
    {
        return $this->getSelected();
    }

    public function hasDependencyValue(): bool
    {
        return $this->hasSelection();
    }

    public function apply(): array|string|null
    {
        if ($this->_isRequired) {
            if (!$this->hasSelection()) {
                if ($this->_isForMany) {
                    $this->values->selected->addError('required', $this->_(
                        'You must select at least one entry'
                    ));
                } else {
                    $this->values->selected->addError('required', $this->_(
                        'You must make a selection'
                    ));
                }
            }
        }

        return $this->getSelected();
    }

    protected function _getSelectionErrors()
    {
        return $this->values->selected;
    }

    // Events
    protected function onClearEvent(): mixed
    {
        unset($this->values->selected);
        return Legacy::$http->redirect('#' . $this->elementId('selector'));
    }

    protected function onRemoveEvent(string $id): mixed
    {
        unset($this->values->selected->{$id});
        return null;
    }
}



// Dependant
trait TForm_DependentDelegate
{
    use opal\query\TFilterConsumer;

    protected array $_dependencies = [];
    protected array $_dependencyMessages = [];

    public function addDependency(
        mixed $value,
        ?string $message = null,
        ?callable $filter = null,
        ?callable $callback = null
    ): static {
        if ($value instanceof IDelegate) {
            $name = $value->getDelegateKey();
        } else {
            $name = uniqid();
        }

        return $this->setDependency($name, $value, $message, $filter, $callback);
    }

    public function setDependency(
        string $name,
        mixed $value,
        ?string $message = null,
        ?callable $filter = null,
        ?callable $callback = null
    ): static {
        $this->_dependencies[$name] = [
            'value' => $value,
            'message' => $message,
            'filter' => $filter,
            'callback' => $callback,
            'normalized' => false,
            'resolved' => null
        ];

        return $this;
    }

    public function hasDependency(string $name): bool
    {
        return isset($this->_dependencies[$name]);
    }

    public function getDependency(string $name): ?array
    {
        if (isset($this->_dependencies[$name])) {
            return $this->_dependencies[$name];
        } else {
            return null;
        }
    }

    public function removeDependency(string $name): void
    {
        unset($this->_dependencies[$name]);
    }

    public function getDependencies(): array
    {
        return $this->_dependencies;
    }

    public function getDependencyMessages(): array
    {
        $this->normalizeDependencyValues();
        $output = [];

        foreach ($this->_dependencies as $name => $dep) {
            if ($dep['resolved']) {
                continue;
            }

            $output[$name] = $dep['message'] ?? $this->_('Unresolved dependency: %n%', ['%n%' => $name]);
        }

        return $output;
    }

    protected function _beforeEvent(string $event): void
    {
        $this->normalizeDependencyValues();
    }

    public function applyFilters(opal\query\IQuery $query)
    {
        if (!$query instanceof opal\query\IWhereClauseQuery) {
            throw Exceptional::Logic(
                'Filter query is not a where clause factory',
                null,
                $query
            );
        }

        if (!empty($this->_filters)) {
            $clause = $query->beginWhereClause();

            foreach ($this->_filters as $filter) {
                $filter->invoke($clause, $this);
            }

            $clause->endClause();
        }

        $this->normalizeDependencyValues();
        $filters = [];
        $clause = null;

        foreach ($this->_dependencies as $name => $dep) {
            if (!$dep['resolved'] || !isset($dep['filter'])) {
                continue;
            }

            if (!$clause) {
                $clause = $query->beginWhereClause();
            }

            core\lang\Callback::factory($dep['filter'])->invoke(
                $clause,
                $dep['value'],
                $this
            );
        }

        if ($clause) {
            $clause->endClause();
        }

        return $this;
    }

    public function normalizeDependencyValues(): void
    {
        foreach ($this->_dependencies as $name => $dep) {
            if ($dep['normalized']) {
                continue;
            }

            $value = $dep['value'];
            $isResolved = null;

            if ($value instanceof IDependencyValueProvider) {
                $isResolved = $value->hasDependencyValue();
                $value = $value->getDependencyValue();
            } elseif ($value instanceof core\IValueContainer) {
                $value = $value->getValue();
                $isResolved = $value !== null;
            } elseif (is_callable($value)) {
                $value = $value($this);
            }

            if ($isResolved === null) {
                $isResolved = (bool)$value;
            }

            if ($dep['callback'] && $isResolved) {
                $doCallback = $hasChanged = false;

                if ($this->hasStore('__dependency:' . $name)) {
                    @list($wasResolved, $lastValue) = $this->getStore('__dependency:' . $name);
                    $hasChanged = $value != $lastValue;

                    if (!$wasResolved || $hasChanged) {
                        $doCallback = true;
                    }
                } else {
                    $doCallback = true;
                }

                core\lang\Callback::factory($dep['callback'])->invoke(
                    $value,
                    $this,
                    $doCallback
                );
            }

            $this->_dependencies[$name]['value'] = $value;
            $this->_dependencies[$name]['normalized'] = true;
            $this->_dependencies[$name]['resolved'] = $isResolved;

            if ($dep['callback']) {
                $this->setStore('__dependency:' . $name, [$isResolved, $value]);
            }
        }
    }
}


// Media
trait TForm_MediaBucketAwareSelector
{
    protected string|BucketRecord $_bucket = 'shared';
    protected ?array $_bucketData = null;
    protected ?IBucket $_bucketHandler = null;

    /**
     * @return $this
     */
    public function setBucket(
        string|BucketRecord $bucket,
        array $values = null
    ): static {
        $this->_bucket = $bucket;
        $this->_bucketData = $values;

        return $this;
    }

    public function getBucket(): string|BucketRecord
    {
        return $this->_bucket;
    }

    public function getBucketData(): ?array
    {
        return $this->_bucketData;
    }


    protected function _setupBucket(): void
    {
        if ($this->_bucket) {
            if (!$this->_bucket instanceof opal\record\IRecord) {
                $this->_bucket = $this->data->media->bucket->ensureSlugExists(
                    $this->_bucket,
                    $this->_bucketData
                );
            }

            $this->_bucketHandler = $this->_bucket->getHandler();
        }
    }
}
