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

interface IForm extends IStoreProvider {
    public function isRenderingInline();
    public function getStateController();
    public function loadDelegate($id, $name, $request=null);
    public function getDelegate($id);
    public function hasDelegate($id);
    public function unloadDelegate($id);

    public function isValid();
    public function fieldName($name);
    public function eventName($name);
    public function elementId($name);
}

interface IActiveForm extends IForm {
    public function handleEvent($name, array $args=array());
    public function handleDelegateEvent($delegateId, $event, $args);
}


interface IAction extends arch\IAction, IActiveForm {
    public function complete($defaultRedirect=null, $success=true);
}


interface IDelegate extends IActiveForm, arch\IContextAware {
    public function getDelegateId();
    public function getDelegateKey();
    public function initialize();
    public function setRenderContext(aura\view\IView $view, aura\view\IContentProvider $content, $isRenderingInline=false);
    public function complete($success);
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

interface IResultProviderDelegate extends IRequirableDelegate {
    public function apply();
}

interface ISelectorDelegate extends IResultProviderDelegate {
    public function isForOne($flag=null);
    public function isForMany($flag=null);

    public function isSelected($id);
    public function setSelected($selected);
    public function getSelected();
    public function hasSelection();
}

interface IInlineFieldRenderableSelectorDelegate extends IModalDelegate, IInlineFieldRenderableDelegate, ISelectorDelegate {}


interface IDependency {
    public function getName();

    public function setContext($context);
    public function getContext();

    public function hasValue();
    public function getValue();

    public function setErrorMessage($message);
    public function getErrorMessage();
}

trait TDependency {

    protected $_name;
    protected $_context;
    protected $_error;

    public function getName() {
        return $this->_name;
    }

    public function setContext($context) {
        $this->_context = $context;
    }

    public function getContext() {
        return $this->_context;
    }

    public function setErrorMessage($error) {
        $this->_error = $error;
        return $this;
    }

    public function getErrorMessage() {
        return $this->_error;
    }
}

interface IDependentDelegate {
    public function addSelectorDependency(ISelectorDelegate $delegate, $error=null, $context=null);
    public function addValueDependency($name, core\collection\IInputTree $value, $error=null, $context=null);
    public function addValueListDependency($name, core\collection\IInputTree $value, $error=null, $context=null);
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
}