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
}

interface IForm extends IStoreProvider, core\IChainable {
    public function isRenderingInline();
    public function getStateController();
    public function loadDelegate($id, $name, $request=null);
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
    public function complete($defaultRedirect=null, $success=true);
    public function isComplete();
}


interface IAction extends arch\IAction, IActiveForm {
    
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

interface IInlineFieldRenderableDelegate {
    public function renderFieldArea($label=null);
    public function renderInlineFieldAreaContent();
    public function renderFieldAreaContent(aura\html\widget\FieldArea $fieldArea);
}

interface ISelfContainedRenderableDelegate {
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

interface ISelectorDelegate extends IResultProviderDelegate {
    public function isForOne($flag=null);
    public function isForMany($flag=null);

    public function isSelected($id);
    public function setSelected($selected);
    public function getSelected();
    public function hasSelection();
}

interface IInlineFieldRenderableSelectorDelegate extends IModalDelegate, IInlineFieldRenderableDelegate, ISelectorDelegate {}

interface IAdapterDelegate extends IParentUiHandlerDelegate, IParentEventHandlerDelegate {
    
}

interface IDependency {
    public function getName();

    public function setContext($context);
    public function getContext();

    public function hasValue();
    public function getValue();
    public function shouldFilter($flag=null);

    public function setErrorMessage($message);
    public function getErrorMessage();

    public function setCallback(Callable $callback=null);
    public function getCallback();
    public function hasCallback();

    public function setApplied($applied=true);
    public function isApplied();
}

trait TDependency {

    protected $_name;
    protected $_context;
    protected $_error;
    protected $_callback;
    protected $_isApplied = false;
    protected $_shouldFilter = true;

    public function getName() {
        return $this->_name;
    }

    public function setContext($context) {
        if($context === false) {
            $context = null;
            $this->_shouldFilter = false;
        }

        $this->_context = $context;
        return $this;
    }

    public function getContext() {
        return $this->_context;
    }

    public function shouldFilter($flag=null) {
        if($flag !== null) {
            $this->_shouldFilter = (bool)$flag;
            return $this;
        }

        return $this->_shouldFilter;
    }

    public function setErrorMessage($error) {
        $this->_error = $error;
        return $this;
    }

    public function getErrorMessage() {
        return $this->_error;
    }

    public function setCallback(Callable $callback=null) {
        $this->_callback = $callback;
        return $this;
    }

    public function getCallback() {
        return $this->_callback;
    }

    public function hasCallback() {
        return $this->_callback !== null;
    }

    public function setApplied($applied=true) {
        $this->_isApplied = (bool)$applied;
        return $this;
    }

    public function isApplied() {
        return $this->_isApplied;
    }
}

interface IDependentDelegate {
    public function addSelectorDependency(ISelectorDelegate $delegate, $error=null, $context=null, $filter=false);
    public function addValueDependency($name, core\collection\IInputTree $value, $error=null, $context=null, $filter=false);
    public function addValueListDependency($name, core\collection\IInputTree $value, $error=null, $context=null, $filter=false);
    public function addGenericDependency($name, $value, $error=null, $context=null);
    public function addFilter($context, $value, $name=null);
    public function addDependency(IDependency $dependency);

    public function getDependency($name);
    public function getDependencies();
    public function getDependenciesByContext($context);
    public function getDependencyValuesByContext($context);
    public function hasDependencyContext($context);
    public function getUnresolvedDependencies();
    public function getUnresolvedDependencyMessages();
    public function applyDependencies(opal\query\IQuery $query);
    public function setDependencyContextApplied($context, $applied=true);
}