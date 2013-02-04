<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\form\template;

use df;
use df\core;
use df\arch;
use df\aura;
use df\opal;
    


interface IModalDelegate {

    public function getAvailableModes();
    public function setDefaultMode($mode);
    public function getDefaultMode();
}

trait TModalDelegate {

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
            throw new arch\form\InvalidArgumentException(
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
            throw new arch\form\DelegateException(
                'Selector delegate has no render handler for '.$mode.' mode'
            );
        }

        return call_user_func_array([$this, $func], $args);
    }
}



interface IInlineFieldRenderableDelegate {
    public function renderFieldArea($label=null);
    public function renderFieldAreaContent(aura\html\widget\FieldArea $fieldArea);
}

trait TInlineFieldRenderableDelegate {

    public function renderFieldArea($label=null) {
        $this->renderFieldAreaContent(
            $output = $this->html->fieldArea($label)
        );

        return $output;
    }
}

interface ISelfContainedRenderableDelegate {
    public function renderFieldSet($legend=null);
    public function renderFieldSetContent(aura\html\widget\FieldSet $fieldSet);
}

trait TSelfContainedRenderableDelegate {

    public function renderFieldSet($legend=null) {
        $this->renderFieldSet(
            $output = $this->html->fieldSet($legend)
        );

        return $output;
    }
}


interface IResultProviderDelegate {
    public function isRequired($flag=null);
    public function apply();
}

trait TResultProviderDelegate {

    protected $_isRequired = false;

    public function isRequired($flag=null) {
        if($flag !== null) {
            $this->_isRequired = (bool)$flag;
            return $this;
        }

        return $this->_isRequired;
    }
}


interface ISelectorDelegate extends IResultProviderDelegate {
    public function isForOne($flag=null);
    public function isForMany($flag=null);

    public function isSelected($id);
    public function setSelected($selected);
    public function getSelected();
    public function hasSelection();
}

trait TSelectorDelegate {

    use TResultProviderDelegate;
    
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

trait TSelectorDelegateQueryTools {

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


trait TValueListSelectorDelegate {

// Selected
    public function isSelected($id) {
        if(!$this->_isForMany) {
            return $this->values['selected'] == $id;
        } else {
            return $this->values->selected->has($id);
        }
    }

    public function setSelected($selected) {
        if(!$this->_isForMany) {
            if($selected instanceof opal\query\record\IRecord) {
                $selected = $selected->getPrimaryManifest();
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
                $this->values->selected->addError('required', $this->_(
                    'You must select at least one entry'
                ));
            }
        }

        return $this->getSelected();
    }

// Events
    protected function _onClearEvent() {
        unset($this->values->selected);
    }

    protected function _onRemoveEvent($id) {
        unset($this->values->selected->{$id});
    }
}




interface IInlineFieldRenderableSelectorDelegate extends IInlineFieldRenderableDelegate, ISelectorDelegate {}

trait TInlineFieldRenderableSelectorDelegate {

    use TModalDelegate;
    use TInlineFieldRenderableDelegate;
    use TSelectorDelegate;
    use TSelectorDelegateQueryTools;

    protected static $_defaultModes = [
        'select' => '_renderOverlaySelector',
        'details' => '_renderInlineDetails'
    ];

    protected function _getDefaultMode() {
        return 'details';
    }

// Render
    public function renderFieldAreaContent(aura\html\widget\FieldArea $fa) {
        $fa->isRequired($this->_isRequired);
        
        $this->_renderModeUi([$fa]);
    }

    protected function _renderInlineDetails(aura\html\widget\FieldArea $fa) {
        $selectList = $this->_fetchSelectionList();

        if($this->_isForMany) {
            // Multiple entry

            $selected = $this->_normalizeQueryResult($selectList);

            if(empty($selected)) {
                $fa->push(
                    $this->html->element('em', $this->_('nothing selected')),
                    $this->html->string('<br />')
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

                $fa->push($this->html->_(
                    [
                        '0' => '%l%',
                        'n > 0' => '%l%<br />and <strong>%c%</strong> more selected'
                    ],
                    [
                        '%l%' => implode('<br />', $displayList),
                        '%c%' => $count
                    ],
                    $count
                ));
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
                        ),

                    $this->html->string('<br />')
                );
            } else {
                // No selection

                $fa->push(
                    $this->html->element('em', $this->_('nothing selected')),
                    $this->html->string('<br />')
                );
            }
        }

        $ba = $fa->addButtonArea();
        $this->_renderDetailsButtonGroup($ba, $selected);
    }

    protected function _renderDetailsButtonGroup(aura\html\widget\ButtonArea $ba, $selected) {
        if(empty($selected)) {
            $ba->push(
                $this->html->eventButton(
                        $this->eventName('beginSelect'),
                        $this->_('Select')
                    )
                    ->setIcon('tick')
                    ->setDisposition('positive')
                    ->shouldValidate(false)
            );
        } else {
            $ba->push(
                $this->html->eventButton(
                        $this->eventName('beginSelect'),
                        $this->_('Change selection')
                    )
                    ->setIcon('edit')
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
    }

    protected function _onEndSelectEvent() {
        $this->_switchMode('select', 'details', function() {
            $this->_state->removeStore('originalSelection');
        });
    }

    protected function _onResetEvent() {
        if($this->_state->hasStore('originalSelection')) {
            $this->setSelected($this->_state->getStore('originalSelection'));
        }
    }
}