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

    public function getStateController() {
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

    public function hasStore($key) {
        return $this->_state->hasStore($key);
    }

    public function getStore($key, $default=null) {
        return $this->_state->getStore($key, $default);
    }

    public function removeStore($key) {
        $this->_state->removeStore($key);
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
    public function eventName($name) {
        $args = array_slice(func_get_args(), 1);
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

        return call_user_func_array([$this, $func], $args);
    }

    protected function onResetEvent() {
        $this->reset();
    }

    protected function onRefreshEvent() {}

    public function handleDelegateEvent($delegateId, $event, $args) {}


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






// Modal
trait TForm_ModalDelegate {

    protected $_defaultMode = null;

    public function getAvailableModes() {
        return array_keys($this->_getModeRenderers());
    }

    protected function _getModeRenderers() {
        if(isset(static::$_modes) && !empty(static::$_modes)) {
            return static::$_modes;
        } else if(isset(static::$_defaultModes)) {
            return static::$_defaultModes;
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

        return call_user_func_array([$this, $func], $args);
    }
}


// Renderable
trait TForm_RenderableDelegate {

    protected $_isStacked = false;

    public function isStacked($flag=null) {
        if($flag !== null) {
            $this->_isStacked = (bool)$flag;
            return $this;
        }

        return $this->_isStacked;
    }
}

// Inline field renderable
trait TForm_InlineFieldRenderableDelegate {

    use TForm_RenderableDelegate;

    public function renderFieldArea($label=null) {
        $this->renderFieldAreaContent(
            $output = $this->html->fieldArea($label)
        );

        return $output;
    }

    public function renderInlineFieldAreaContent() {
        return $this->renderFieldArea()->renderInputArea();
    }
}



// Self contained renderable
trait TForm_SelfContainedRenderableDelegate {

    use TForm_RenderableDelegate;

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

    public function isRequired($flag=null) {
        if($flag !== null) {
            $this->_isRequired = (bool)$flag;
            return $this;
        }

        return $this->_isRequired;
    }
}



// Selector
trait TForm_SelectorDelegate {

    use TForm_RequirableDelegate;

    protected $_isForMany = true;

    public function isForOne($flag=null) {
        if($flag !== null) {
            $this->_isForMany = !(bool)$flag;
            return $this;
        }

        return !$this->_isForMany;
    }

    public function isForMany($flag=null) {
        if($flag !== null) {
            $this->_isForMany = (bool)$flag;
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

    public function addDependency($value, $message=null, $filter=null) {
        return $this->setDependency(uniqid(), $value, $message, $filter);
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
        $this->_normalizeDependencyValues();
        $output = [];

        foreach($this->_dependencies as $name => $dep) {
            if($dep['resolved']) {
                continue;
            }

            $output[$name] = isset($dep['message']) ?
                $dep['message'] : $this->_('Unresolved dependency: %n%', ['%n%' => $name]);
        }

        return $output;
    }

    public function applyFilters(opal\query\IQuery $query) {
        if(!empty($this->_filters)) {
            $clause = $query->beginWhereClause();

            foreach($this->_filters as $filter) {
                $filter->invoke($clause, $this);
            }

            $clause->endClause();
        }

        $this->_normalizeDependencyValues();
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

    private function _normalizeDependencyValues() {
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
                $value = call_user_func_array($value, [$this]);
            }

            if($isResolved === null) {
                $isResolved = (bool)$value;
            }

            if($dep['callback'] && $isResolved) {
                @list($wasResolved, $lastValue) = $this->getStore('__dependency:'.$name);

                if(!$wasResolved || $value != $lastValue) {
                    core\lang\Callback::factory($dep['callback'])->invoke(
                        $value, $this
                    );
                }
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