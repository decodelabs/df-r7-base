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

    use core\TChainable;
    
    public $view;
    //public $html;
    public $content;
    public $values;
    
    protected $_isRenderingInline = false;
    protected $_state;
    protected $_delegates = array();
    
    protected function _init() {}
    protected function _setDefaultValues() {}
    protected function _setupDelegates() {}
    protected function _onInitComplete() {}
    
    
    public function __call($method, $args) {
        return call_user_func_array(array($this->_context, $method), $args);
    }
    
    public function __get($key) {
        if($key == 'view') {
            return $this->view;
        } else if($key == 'context') {
            return $this->_context;
        }

        if($this->view && ($output = $this->view->getHelper($key, true))) {
            return $output;
        }

        return $this->_context->__get($key);
    }

    public function getStateController() {
        return $this->_state;
    }
    
// Delegates
    public function loadDelegate($id, $name, $request=null) {
        if(false !== strpos($id, '.')) {
            throw new InvalidArgumentException(
                'Delegate IDs must not contain . character'
            );
        }

        $context = $this->_context->spawnInstance($request);
        $path = $context->location->getController();
        $area = $context->location->getArea();
        
        if(!empty($path)) {
            $parts = explode('/', $path);
        } else {
            $parts = array();
        }
        
        $type = $context->getRunMode();

        $parts[] = '_formDelegates';
        $nameParts = explode('/', $name);
        $topName = array_pop($nameParts);

        if(!empty($nameParts)) {
            $parts = array_merge($parts, $nameParts);
        }

        $parts[] = ucfirst($topName);
        
        $class = 'df\\apex\\directory\\'.$area.'\\'.implode('\\', $parts);
        
        if(!class_exists($class)) {
            $class = 'df\\apex\\directory\\shared\\'.implode('\\', $parts);

            if(!class_exists($class)) {
                throw new DelegateException(
                    'Delegate '.$name.' could not be found at ~'.$area.'/'.$path
                );
            }
        }
        
        return $this->_delegates[$id] = new $class(
            $context,
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
    
    
// Values
    public function isValid() {
        if($this->_state && !$this->_state->getValues()->isValid()) {
            return false;
        }
        
        foreach($this->_delegates as $delegate) {
            if(!$delegate->isValid()) {
                return false;
            }
        }
        
        return true;
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
    public function handleEvent($name, array $args=array()) {
        $func = '_on'.ucfirst($name).'Event';
        
        if(!method_exists($this, $func)) {
            $func = '_onDefaultEvent';
            
            if(!method_exists($this, $func)) {
                throw new EventException(
                    'Event '.$name.' does not have a handler'
                );
            }
        }
        
        return call_user_func_array(array($this, $func), $args);
    }

    protected function _onResetEvent() {
        $this->_state->reset();
    }

    public function handleDelegateEvent($delegateId, $event, $args) {}


    public function getAvailableEvents() {
        $output = [];
        $ref = new \ReflectionClass($this);

        foreach($ref->getMethods() as $method) {
            if(preg_match('/^\_on([A-Z\_][a-zA-Z0-9_]*)Event$/', $method->getName(), $matches)) {
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


    protected function _setMode($mode) {
        $this->_state->setStore('mode', $mode);
    }

    protected function _getMode($default=null) {
        if($default === null) {
            $default = $this->getDefaultMode();
        }

        return $this->_state->getStore('mode', $default);
    }

    protected function _switchMode($from, $to, Callable $do=null) {
        if(!is_array($from)) {
            $from = [$from];
        }

        $mode = $this->_getMode();

        if(!in_array($mode, $from)) {
            return;
        }

        $this->_setMode($to);

        if($do) {
            $do();
        }
    }


    protected function _renderModeUi(array $args) {
        $mode = $this->_getMode();
        $modes = $this->_getModeRenderers();

        if(isset($modes[$mode])) {
            $func = $modes[$mode];
        } else {
            $func = 'details';
        }

        if(!method_exists($this, $func)) {
            throw new DelegateException(
                'Selector delegate has no render handler for '.$mode.' mode'
            );
        }

        return call_user_func_array([$this, $func], $args);
    }
}



// Inline field renderable
trait TForm_InlineFieldRenderableDelegate {

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

trait TForm_SelectorDelegateQueryTools {

    protected function _fetchSelectionList() {
        $selected = $this->getSelected();

        if(empty($selected)) {
            return $selected;
        }

        if(!$this->_isForMany) {
            $selected = [$selected];
        }

        return $this->_fetchResultList($selected);
    }

    abstract protected function _fetchResultList(array $ids);

    protected function _normalizeQueryResult($result) {
        if($result instanceof opal\query\IQuery) {
            $result = $result->toArray();
        }

        if(!$result instanceof \Iterator
        && !$result instanceof core\collection\ICollection
        && !is_array($result)) {
            $result = array();
        }

        return $result;
    }

    protected function _extractQueryResult($result) {
        $result = $this->_normalizeQueryResult($result);

        foreach($result as $entry) {
            return $entry;
        }
    }

    protected function _isQueryResultEmpty($result) {
        if($result instanceof core\collection\ICollection) {
            return $result->isEmpty();
        } else if(is_array($result)) {
            return empty($result);
        } else {
            return true;
        }
    }

    protected function _getResultId($result) {
        return $result['id'];
    }

    protected function _getResultDisplayName($result) {
        return $result['name'];
    }
}


trait TForm_ValueListSelectorDelegate {

// Selected
    public function isSelected($id) {
        if(!$this->_isForMany) {
            return $this->values['selected'] == $id;
        } else {
            return $this->values->selected->has($id);
        }
    }

    public function setSelected($selected) {
        if($selected === null) {
            unset($this->values->selected);
            return $this;
        }

        if(!$this->_isForMany) {
            if($selected instanceof opal\record\IRecord) {
                $selected = $selected->getPrimaryKeySet();
            }

            $this->values->selected = $selected;
        } else {
            if(!is_array($selected)) {
                $selected = (array)$selected;
            }

            foreach($selected as $id) {
                $this->values->selected[$id] = $id;
            }
        }

        return $this;
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
    protected function _onClearEvent() {
        unset($this->values->selected);
        return $this->http->redirect('#'.$this->elementId('selector'));
    }

    protected function _onRemoveEvent($id) {
        unset($this->values->selected->{$id});
    }
}



// Inline field renderable selector
trait TForm_InlineFieldRenderableSelectorDelegate {

    use TForm_ModalDelegate;
    use TForm_InlineFieldRenderableDelegate;
    use TForm_SelectorDelegate;
    use TForm_SelectorDelegateQueryTools;

    protected static $_defaultModes = [
        'select' => '_renderOverlaySelector',
        'details' => '_renderInlineDetails'
    ];

    protected function _getDefaultMode() {
        return 'details';
    }

// Render
    public function renderFieldAreaContent(aura\html\widget\FieldArea $fa) {
        $fa->setId($this->elementId('selector'));
        $fa->isRequired($this->_isRequired);
        
        $this->_renderModeUi([$fa]);
    }

    protected function _renderInlineDetails(aura\html\widget\FieldArea $fa) {
        $fa->addClass('delegate-selector');

        if($this instanceof arch\form\IDependentDelegate) {
            $messages = $this->getUnresolvedDependencyMessages();

            if(!empty($messages)) {
                $fa->push($this->html->fieldError($messages));
                return;
            }
        }

        if($messages = $this->_getSelectionErrors()) {
            $fa->push($this->html->fieldError($messages));
        }

        $fa->push($this->html->string('<div class="widget-selection"><div class="body">'));
        $selectList = $this->_fetchSelectionList();

        if($this->_isForMany) {
            // Multiple entry

            $selected = $this->_normalizeQueryResult($selectList);

            if(empty($selected)) {
                $fa->push(
                    $this->html->element('em', $this->_('nothing selected'))
                );
            } else {
                $tempList = $selected;
                $count = count($selected);
                $displayList = array();

                for($i = 0; $i < 3 && !empty($tempList); $i++) {
                    $count--;

                    $displayList[] = $this->html->element(
                        'strong', 
                        $this->_getResultDisplayName(array_shift($tempList))
                    );
                }

                if($count) {
                    $displayList[] = $this->html->_(
                        'and <strong>%c%</strong> more selected', 
                        ['%c%' => $count]
                    );
                }

                $fa->push($this->html->bulletList($displayList));
            }
        } else {
            // Single entry

            $selected = $this->_extractQueryResult($selectList);

            if($selected) {
                // Selection made

                $resultId = $this->_getResultId($selected);
                $resultName = $this->_getResultDisplayName($selected);

                $fa->push(
                    $this->html->element('strong', $resultName),

                    $this->html->hidden(
                            $this->fieldName('selected'),
                            $resultId
                        )
                );
            } else {
                // No selection

                $fa->push(
                    $this->html->element('em', $this->_('nothing selected'))
                );
            }
        }

        $fa->push($this->html->string('</div>'));

        $ba = $fa->addButtonArea();
        $this->_renderDetailsButtonGroup($ba, $selected);

        $fa->push($this->html->string('</div>'));
    }

    protected function _renderDetailsButtonGroup(aura\html\widget\ButtonArea $ba, $selected) {
        if(empty($selected)) {
            $ba->push(
                $this->html->eventButton(
                        $this->eventName('beginSelect'),
                        $this->_('Select')
                    )
                    ->setIcon('select')
                    ->setDisposition('positive')
                    ->shouldValidate(false)
            );
        } else {
            $ba->push(
                $this->html->eventButton(
                        $this->eventName('beginSelect'),
                        $this->_('Select')
                    )
                    ->setIcon('select')
                    ->setDisposition('operative')
                    ->shouldValidate(false),

                $this->html->eventButton(
                        $this->eventName('clear'),
                        $this->_('Clear')
                    )
                    ->setIcon('remove')
                    ->shouldValidate(false)
            );
        }
    }

    protected function _renderOverlaySelector(aura\html\widget\FieldArea $fa) {
        $this->_renderInlineDetails($fa);
        $ol = $fa->addOverlay($fa->getLabelBody());
        
        return $this->_renderOverlaySelectorContent($ol);
    }

    abstract protected function _renderOverlaySelectorContent(aura\html\widget\Overlay $ol);
    abstract protected function _getSelectionErrors();


// Events
    protected function _onBeginSelectEvent() {
        $this->_switchMode('details', 'select', function() {
            $this->_state->setStore('originalSelection', $this->getSelected());
        });
    }

    protected function _onCancelSelectEvent() {
        $this->_switchMode('select', 'details', function() {
            if($this->_state->hasStore('originalSelection')) {
                $this->setSelected($this->_state->getStore('originalSelection'));
            }
        });

        return $this->http->redirect('#'.$this->elementId('selector'));
    }

    protected function _onEndSelectEvent() {
        $this->_switchMode('select', 'details', function() {
            $this->_state->removeStore('originalSelection');
        });

        return $this->http->redirect('#'.$this->elementId('selector'));
    }

    protected function _onResetEvent() {
        if($this->_state->hasStore('originalSelection')) {
            $this->setSelected($this->_state->getStore('originalSelection'));
        }
    }
}




// Dependant
trait TForm_DependentDelegate {

    protected $_dependencies = array();

    public function addSelectorDependency(ISelectorDelegate $delegate, $error=null, $context=null) {
        return $this->addDependency(new arch\form\dependency\Selector($delegate, $error, $context));
    }

    public function addValueDependency($name, core\collection\IInputTree $value, $error=null, $context=null) {
        return $this->addDependency(new arch\form\dependency\Value($name, $value, $error, $context));
    }
    
    public function addValueListDependency($name, core\collection\IInputTree $value, $error=null, $context=null) {
        return $this->addDependency(new arch\form\dependency\ValueList($name, $value, $error, $context));
    }

    public function addGenericDependency($name, $value, $error=null, $context=null) {
        return $this->addDependency(new arch\form\dependency\Generic($name, $value, $error, $context));
    }
    
    public function addFilter($context, $value, $name=null) {
        return $this->addDependency(new arch\form\dependency\Filter($context, $value, $name=null));
    }

    public function addDependency(IDependency $dependency) {
        $this->_dependencies[$dependency->getName()] = $dependency;
        return $this;
    }


    public function getDependency($name) {
        if(isset($this->_dependencies[$name])) {
            return $this->_dependencies[$name];
        }
    }

    public function getDependencies() {
        return $this->_dependencies;
    }

    public function getDependenciesByContext($context) {
        $output = array();

        foreach($this->_dependencies as $name => $dep) {
            if($dep->getContext() == $context) {
                $output[$name] = $dep;
            }
        }

        return $output;
    }

    public function getDependencyValuesByContext($context) {
        $output = array();

        foreach($this->_dependencies as $name => $dep) {
            if($dep->getContext() == $context && $dep->hasValue()) {
                $output[$name] = $dep->getValue();
            }
        }

        return $output;
    }

    public function hasDependencyContext($context) {
        foreach($this->_dependencies as $name => $dep) {
            if($dep->getContext() == $context) {
                return true;
            }
        }

        return false;
    }

    public function getUnresolvedDependencies() {
        $output = array();

        foreach($this->_dependencies as $name => $dependency) {
            if(!$dependency->hasValue()) {
                $output[$name] = $dependency;
            }
        }

        return $output;
    }

    public function getUnresolvedDependencyMessages() {
        $output = array();

        foreach($this->getUnresolvedDependencies() as $name => $dep) {
            $message = $dep->getErrorMessage();

            if(empty($message)) {
                $message = $this->_('Unresolved dependency: %n%', ['%n%' => $dep->getName()]);
            }

            $output[$name] = $message;
        }

        return $output;
    }
}