<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\node;

use df;
use df\core;
use df\arch;
use df\aura;
use df\opal;
use df\mesh;


// BASE
trait TForm {

    use core\lang\TChainable;
    use aura\view\TCascadingHelperProvider;

    public $content;
    public $values;

    protected $_isRenderingInline = false;
    protected $_state;
    protected $_delegates = [];

    protected function init() {}
    protected function setDefaultValues() {}
    protected function loadDelegates() {}
    protected function afterInit() {}

    public function getState() {
        return $this->_state;
    }

// Delegates
    public function loadDelegate($id, $name) {
        if(false !== strpos($id, '.')) {
            throw new InvalidArgumentException(
                'Delegate IDs must not contain . character'
            );
        }

        $location = $this->context->extractDirectoryLocation($name);
        $context = $this->context->spawnInstance($location);
        $path = $context->location->getController();
        $area = $context->location->getArea();

        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = [];
        }

        $type = $context->getRunMode();

        $parts[] = '_formDelegates';
        $nameParts = explode('/', $name);
        $topName = array_pop($nameParts);

        if(!empty($nameParts)) {
            $parts = array_merge($parts, $nameParts);
        }

        $parts[] = ucfirst($topName);
        $state = $this->_state->getDelegateState($id);
        $mainId = $this->_getDelegateIdPrefix().$id;

        $class = 'df\\apex\\directory\\'.$area.'\\'.implode('\\', $parts);

        if(!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\'.implode('\\', $parts);

            if(!class_exists($class)) {
                try {
                    $scaffold = arch\scaffold\Base::factory($context);
                    return $this->_delegates[$id] = $scaffold->loadFormDelegate($name, $state, $mainId);
                } catch(arch\scaffold\IException $e) {}

                throw new DelegateException(
                    'Delegate '.$name.' could not be found at ~'.$area.'/'.$path
                );
            }
        }

        return $this->_delegates[$id] = new $class($context, $state, $mainId);
    }

    public function directLoadDelegate($id, $class) {
        if(!class_exists($class)) {
            throw new DelegateException(
                'Cannot direct load delegate '.$id.' - class not found'
            );
        }

        return $this->_delegates[$id] = new $class(
            $this->context,
            $this->_state->getDelegateState($id),
            $this->_getDelegateIdPrefix().$id
        );
    }

    public function getDelegate($id) {
        if(!is_array($id)) {
            $id = explode('.', trim($id, ' .'));
        }

        if(empty($id)) {
            throw new DelegateException(
                'Empty delegate id detected'
            );
        }

        $top = array_shift($id);

        if(!isset($this->_delegates[$top])) {
            throw new DelegateException(
                'Delegate '.$top.' could not be found'
            );
        }

        $output = $this->_delegates[$top];

        if(!empty($id)) {
            $output = $output->getDelegate($id);
        }

        return $output;
    }

    public function hasDelegate($id) {
        if(!is_array($id)) {
            $id = explode('.', trim($id, ' .'));
        }

        if(empty($id)) {
            return false;
        }

        $top = array_shift($id);

        if(!isset($this->_delegates[$top])) {
            return false;
        }

        $delegate = $this->_delegates[$top];

        if(!empty($id)) {
            return $delegate->hasDelegate($id);
        }

        return true;
    }

    public function unloadDelegate($id) {
        if(!is_array($id)) {
            $id = explode('.', trim($id, ' .'));
        }

        if(empty($id)) {
            throw new DelegateException(
                'Empty delegate id detected'
            );
        }

        $top = array_shift($id);

        if(!isset($this->_delegates[$top])) {
            throw new DelegateException(
                'Delegate '.$top.' could not be found'
            );
        }

        $delegate = $this->_delegates[$top];

        if(!empty($id)) {
            $delegate->unloadDelegate($id);
            return $this;
        }

        $this->_state->clearDelegateState($top);
        unset($this->_delegates[$top]);

        return $this;
    }


    protected function _getDelegateIdPrefix() {
        if($this instanceof IDelegate) {
            return $this->_delegateId.'.';
        }

        return '';
    }


    public function isRenderingInline() {
        return $this->_isRenderingInline;
    }


    public function offsetSet($id, $name) {
        return $this->loadDelegate($id, $name);
    }

    public function offsetGet($id) {
        return $this->getDelegate($id);
    }

    public function offsetExists($id) {
        return $this->hasDelegate($id);
    }

    public function offsetUnset($id) {
        return $this->unloadDelegate($id);
    }



// Store
    public function setStore($key, $value) {
        $this->_state->setStore($key, $value);
        return $this;
    }

    public function hasStore(...$keys): bool {
        return $this->_state->hasStore(...$keys);
    }

    public function getStore($key, $default=null) {
        return $this->_state->getStore($key, $default);
    }

    public function removeStore(...$keys) {
        $this->_state->removeStore(...$keys);
        return $this;
    }

    public function clearStore() {
        $this->_state->clearStore();
        return $this;
    }


// Delivery
    public function isValid() {
        if($this->_state && !$this->_state->values->isValid()) {
            return false;
        }

        foreach($this->_delegates as $delegate) {
            if(!$delegate->isValid()) {
                return false;
            }
        }

        return true;
    }

    public function countErrors() {
        $output = $this->values->countErrors();

        foreach($this->_delegates as $delegate) {
            $output += $delegate->countErrors();
        }

        return $output;
    }

    public function complete($success=true, $failure=null) {
        if($this->isValid() || ($success && !is_callable($success))) {
            $output = $default = null;

            if(is_string($success) || $success instanceof arch\IRequest) {
                $default = $success;
                $success = null;
            } else if(is_callable($success)) {
                $output = core\lang\Callback::call($success);

                if(is_string($output) || $output instanceof arch\IRequest) {
                    $default = $output;
                    $output = null;
                }
            }

            if($output === false) {
                return;
            } else if($output === true) {
                $output = null;
            }

            $this->_isComplete = true;
            $completeOutput = $this->_getCompleteRedirect($default);

            if(!$output) {
                $output = $completeOutput;
            }

            return $output;
        } else {
            return core\lang\Callback::call($failure);
        }
    }

    protected function _getCompleteRedirect($default=null, $success=true) {
        if($default === null) {
            $default = $this->getDefaultRedirect();
        }

        if($this->request->getType() == 'Html') {
            return $this->http->defaultRedirect($default, $success, $this->_state->referrer);
        } else if($default) {
            return $this->http->redirect($default);
        }
    }


// Names
    public function eventName($name, ...$args) {
        $output = $this->_getDelegateIdPrefix().$name;

        if(!empty($args)) {
            foreach($args as $i => $arg) {
                $args[$i] = '\''.addslashes($arg).'\'';
            }

            $output .= '('.implode(',', $args).')';
        }

        return $output;
    }



// Events
    public function handleEvent($name, array $args=[]) {
        $func = 'on'.ucfirst($name).'Event';

        if(!method_exists($this, $func)) {
            $func = 'onDefaultEvent';

            if(!method_exists($this, $func)) {
                throw new EventException(
                    'Event '.$name.' does not have a handler'
                );
            }
        }

        $this->_beforeEvent($name);
        return $this->{$func}(...$args);
    }

    protected function _beforeEvent($event) {}

    protected function onResetEvent() {
        $this->reset();
    }

    protected function onRefreshEvent() {}

    public function handleDelegateEvent($delegateId, $event, $args) {}

    public function triggerPostEvent(IActiveForm $target, string $event, array $args) {
        if($this !== $target) {
            $this->handlePostEvent($target, $event, $args);
        }

        foreach($this->_delegates as $id => $delegate) {
            $delegate->triggerPostEvent($target, $event, $args);
        }

        return $this;
    }

    public function handlePostEvent(IActiveForm $target, string $event, array $args) {}


    public function getAvailableEvents() {
        $output = [];
        $ref = new \ReflectionClass($this);

        foreach($ref->getMethods() as $method) {
            if(preg_match('/^\on([A-Z\_][a-zA-Z0-9_]*)Event$/', $method->getName(), $matches)) {
                $output[] = $this->eventName(lcfirst($matches[1]));
            }
        }

        foreach($this->_delegates as $delegate) {
            $output = array_merge($output, $delegate->getAvailableEvents());
        }

        return $output;
    }
}




// Parent renderer
trait TForm_ParentUiHandlerDelegate {

    final public function renderUi() {
        if($decorator = arch\decorator\Delegate::factory($this)) {
            $decorator->renderUi();
        } else {
            $this->createUi();
        }
    }

    abstract protected function createUi();
}




// Modal
trait TForm_ModalDelegate {

    protected $_defaultMode = null;

    public function getAvailableModes() {
        return array_keys($this->_getModeRenderers());
    }

    protected function _getModeRenderers() {
        if(isset(static::$_modes) && !empty(static::$_modes)) {
            return static::$_modes;
        } else if(defined('static::DEFAULT_MODES')) {
            return static::DEFAULT_MODES;
        }
    }

    public function setDefaultMode($mode) {
        if($mode === null) {
            $this->_defaultMode = null;
            return $this;
        }


        $modes = $this->getAvailableModes();

        if(!in_array($mode, $modes)) {
            throw new InvalidArgumentException(
                'Mode '.$mode.' is not recognised in this form'
            );
        }

        $this->_defaultMode = $mode;
        return $this;
    }

    public function getDefaultMode() {
        if(empty($this->_defaultMode)) {
            $this->_defaultMode = $this->_getDefaultMode();
        }

        return $this->_defaultMode;
    }

    abstract protected function _getDefaultMode();

    protected function setMode($mode) {
        $this->_state->setStore('mode', $mode);
    }

    protected function getMode($default=null) {
        if($default === null) {
            $default = $this->getDefaultMode();
        }

        return $this->_state->getStore('mode', $default);
    }

    protected function switchMode($from, $to, $do=null) {
        if(!is_array($from)) {
            $from = [$from];
        }

        $mode = $this->getMode();

        if(!in_array($mode, $from)) {
            return;
        }

        $this->setMode($to);

        if($do) {
            core\lang\Callback::factory($do)->invoke($this, $from, $to);
        }
    }

    protected function createModeUi(array $args) {
        $mode = $this->getMode();
        $modes = $this->_getModeRenderers();
        $func = null;

        if(isset($modes[$mode])) {
            $func = $modes[$mode];
        }

        if(!$func || !method_exists($this, $func)) {
            throw new DelegateException(
                'Selector delegate has no render handler for '.$mode.' mode'
            );
        }

        return $this->{$func}(...$args);
    }
}


// Inline field renderable
trait TForm_InlineFieldRenderableDelegate {

    public function renderField($label=null) {
        $this->renderFieldContent(
            $output = $this->html->field($label)
        );

        return $output;
    }

    public function renderInlineFieldContent() {
        return $this->renderField()->renderInputArea();
    }
}



// Self contained renderable
trait TForm_SelfContainedRenderableDelegate {

    public function renderFieldSet($legend=null) {
        $this->renderContainerContent(
            $output = $this->html->fieldSet($legend)
        );

        return $output;
    }

    public function renderContainer() {
        $this->renderContainerContent(
            $output = $this->html->container()
        );

        return $output;
    }
}


// Result provider
trait TForm_RequirableDelegate {

    protected $_isRequired = false;

    public function isRequired(bool $flag=null) {
        if($flag !== null) {
            $this->_isRequired = $flag;
            return $this;
        }

        return $this->_isRequired;
    }
}



// Selector
trait TForm_SelectorDelegate {

    use TForm_RequirableDelegate;

    protected $_isForMany = true;

    public function isForOne(bool $flag=null) {
        if($flag !== null) {
            $this->_isForMany = !$flag;
            return $this;
        }

        return !$this->_isForMany;
    }

    public function isForMany(bool $flag=null) {
        if($flag !== null) {
            $this->_isForMany = $flag;
            return $this;
        }

        return $this->_isForMany;
    }
}



trait TForm_ValueListSelectorDelegate {

// Selected
    public function isSelected($id) {
        $id = (string)$id;

        if(!$this->_isForMany) {
            return $this->values['selected'] == $id;
        } else {
            return $this->values->selected->has($id);
        }
    }

    public function setSelected($selected) {
        unset($this->values->selected);

        if($selected === null) {
            return $this;
        }

        return $this->addSelected($selected);
    }

    public function addSelected($selected) {
        if(!$this->_isForMany) {
            $this->values->selected = $this->_normalizeSelection($selected);
        } else {
            if(!is_array($selected)) {
                $selected = (array)$selected;
            }

            foreach($selected as $id) {
                $id = $this->_normalizeSelection($id);
                $this->values->selected[$id] = $id;
            }
        }

        return $this;
    }

    protected function _normalizeSelection($selection) {
        if($selection instanceof opal\record\IRecord) {
            $selection = $selection->getPrimaryKeySet();
        } else if($selection instanceof opal\record\IPartial) {
            if($selection->isBridge()) {
                core\stub($selection);
            } else {
                $selection = $selection->getPrimaryKeySet();
            }
        }

        return $this->_sanitizeSelection((string)$selection);
    }

    protected function _sanitizeSelection($selection) {
        return $selection;
    }

    public function getSelected() {
        if(!$this->_isForMany) {
            return $this->values['selected'];
        } else {
            return $this->values->selected->toArray();
        }
    }

    public function hasSelection() {
        if(!$this->_isForMany) {
            return $this->values->selected->hasValue();
        } else {
            return !$this->values->selected->isEmpty();
        }
    }

    public function clearSelection() {
        return $this->setSelected(null);
    }

    public function removeSelected($id) {
        if(!$this->_isForMany) {
            unset($this->values->selected);
        } else {
            unset($this->values->selected->{$id});
        }

        return $this;
    }

    public function getDependencyValue() {
        return $this->getSelected();
    }

    public function hasDependencyValue() {
        return $this->hasSelection();
    }

    public function apply() {
        if($this->_isRequired) {
            if(!$this->hasSelection()) {
                if($this->_isForMany) {
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

    protected function _getSelectionErrors() {
        return $this->values->selected;
    }

// Events
    protected function onClearEvent() {
        unset($this->values->selected);
        return $this->http->redirect('#'.$this->elementId('selector'));
    }

    protected function onRemoveEvent($id) {
        unset($this->values->selected->{$id});
    }
}



// Dependant
trait TForm_DependentDelegate {

    use opal\query\TFilterConsumer;

    protected $_dependencies = [];
    protected $_dependencyMessages = [];

    public function addDependency($value, $message=null, $filter=null, $callback=null) {
        if($value instanceof IDelegate) {
            $name = $value->getDelegateKey();
        } else {
            $name = uniqid();
        }

        return $this->setDependency($name, $value, $message, $filter, $callback);
    }

    public function setDependency($name, $value, $message=null, $filter=null, $callback=null) {
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

    public function hasDependency($name) {
        return isset($this->_dependencies[$name]);
    }

    public function getDependency($name) {
        if(isset($this->_dependencies[$name])) {
            return $this->_dependencies[$name];
        }
    }

    public function removeDependency($name) {
        unset($this->_dependencies[$name]);
        return $this;
    }

    public function getDependencies() {
        return $this->_dependencies;
    }

    public function getDependencyMessages() {
        $this->normalizeDependencyValues();
        $output = [];

        foreach($this->_dependencies as $name => $dep) {
            if($dep['resolved']) {
                continue;
            }

            $output[$name] = $dep['message'] ?? $this->_('Unresolved dependency: %n%', ['%n%' => $name]);
        }

        return $output;
    }

    protected function _beforeEvent($event) {
        $this->normalizeDependencyValues();
    }

    public function applyFilters(opal\query\IQuery $query) {
        if(!empty($this->_filters)) {
            $clause = $query->beginWhereClause();

            foreach($this->_filters as $filter) {
                $filter->invoke($clause, $this);
            }

            $clause->endClause();
        }

        $this->normalizeDependencyValues();
        $filters = [];
        $clause = null;

        foreach($this->_dependencies as $name => $dep) {
            if(!$dep['resolved'] || !isset($dep['filter'])) {
                continue;
            }

            if(!$clause) {
                $clause = $query->beginWhereClause();
            }

            core\lang\Callback::factory($dep['filter'])->invoke(
                $clause, $dep['value'], $this
            );
        }

        if($clause) {
            $clause->endClause();
        }

        return $this;
    }

    public function normalizeDependencyValues() {
        foreach($this->_dependencies as $name => $dep) {
            if($dep['normalized']) {
                continue;
            }

            $value = $dep['value'];
            $isResolved = null;

            if($value instanceof IDependencyValueProvider) {
                $isResolved = $value->hasDependencyValue();
                $value = $value->getDependencyValue();
            } else if($value instanceof core\IValueContainer) {
                $value = $value->getValue();
                $isResolved = $value !== null;
            } else if(is_callable($value)) {
                $value = $value($this);
            }

            if($isResolved === null) {
                $isResolved = (bool)$value;
            }

            if($dep['callback'] && $isResolved) {
                $doCallback = $hasChanged = false;

                if($this->hasStore('__dependency:'.$name)) {
                    @list($wasResolved, $lastValue) = $this->getStore('__dependency:'.$name);
                    $hasChanged = $value != $lastValue;

                    if(!$wasResolved || $hasChanged) {
                        $doCallback = true;
                    }
                } else {
                    $doCallback = true;
                }

                core\lang\Callback::factory($dep['callback'])->invoke(
                    $value, $this, $doCallback
                );
            }

            $this->_dependencies[$name]['value'] = $value;
            $this->_dependencies[$name]['normalized'] = true;
            $this->_dependencies[$name]['resolved'] = $isResolved;

            if($dep['callback']) {
                $this->setStore('__dependency:'.$name, [$isResolved, $value]);
            }
        }
    }
}


// Media
trait TForm_MediaBucketAwareSelector {

    protected $_bucket = 'shared';
    protected $_bucketData;
    protected $_bucketHandler;

    public function setBucket($bucket, array $values=null) {
        $this->_bucket = $bucket;
        $this->_bucketData = $values;

        return $this;
    }

    public function getBucket() {
        return $this->_bucket;
    }

    public function getBucketData() {
        return $this->_bucketData;
    }


    protected function _setupBucket() {
        if(isset($this->_bucketData['context1'])
        && $this->_bucketData['context1'] instanceof arch\node\ISelectorDelegate) {
            if(!$this->_bucketData['context1']->hasSelection()) {
                $this->_bucket = null;
                $this->addDependency(
                    $this->_bucketData['context1'],
                    $this->_('Please make a selection')
                );
            } else {
                $id = $this->_bucketData['context1']->getSelected();
                $locator = clone $this->_bucketData['context1']->getSourceEntityLocator();
                $locator->setId($id);
                $this->_bucketData['context1'] = $locator;
            }
        }

        if(isset($this->_bucketData['context2'])
        && $this->_bucketData['context2'] instanceof arch\node\ISelectorDelegate) {
            if(!$this->_bucketData['context2']->hasSelection()) {
                $this->_bucket = null;
                $this->addDependency(
                    $this->_bucketData['context1'],
                    $this->_('Please make a selection')
                );
            } else {
                $id = $this->_bucketData['context2']->getSelected();
                $locator = clone $this->_bucketData['context2']->getSourceEntityLocator();
                $locator->setId($id);
                $this->_bucketData['context2'] = $locator;
            }
        }

        if($this->_bucket) {
            if(!$this->_bucket instanceof opal\record\IRecord) {
                $this->_bucket = $this->data->media->bucket->ensureSlugExists(
                    $this->_bucket, $this->_bucketData
                );
            }

            $this->_bucketHandler = $this->_bucket->getHandler();
        }
    }
}