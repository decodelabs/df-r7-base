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
    
abstract class SearchSelectorDelegate extends arch\form\Delegate implements 
    ISelectorDelegate,
    IInlineFieldRenderableDelegate {

    use TSelectorDelegate;
    use TSelectorDelegateQueryTools;
    use TInlineFieldRenderableDelegate;

    public function renderFieldAreaContent(aura\html\widget\FieldArea $fa) {
        switch($this->_state->getStore('mode', 'details')) {
            case 'select':
                return $this->_renderOverlaySelector($fa);

            case 'details':
            default:
                return $this->_renderInlineDetails($fa);
        }
    }

    protected function _renderInlineDetails(aura\html\widget\FieldArea $fa) {
        $selectList = $this->_fetchSelectionList();
        $ba = $this->html->buttonArea();

        if($this->_isForMany) {
            // Multiple entry

            $selected = $this->_normalizeQueryResult($selectList);

            if(empty($selected)) {
                $fa->push(
                    $this->html->element('em', $this->_('nothing selected')),
                    $this->html->string('<br />')
                );

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
                $count = count($selected);
                $displayList = array();

                for($i = 0; $i < 3 && !empty($selected); $i++) {
                    $count--;

                    $displayList[] = $this->html->element(
                        'strong', 
                        $this->_getResultDisplayName(array_shift($selected))
                    );
                }

                $fa->push($this->html->_(
                    [
                        '0' => '%l%',
                        'n > 0' => '%l% and %c% more selected'
                    ],
                    [
                        '%l%' => implode('/', $displayList),
                        '%c%' => $count
                    ],
                    $count
                ));

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

                $ba->push(
                    $this->html->eventButton(
                            $this->eventName('beginSelect'),
                            $this->_('Select another')
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
            } else {
                // No selection

                $fa->push(
                    $this->html->element('em', $this->_('nothing selected')),
                    $this->html->string('<br />')
                );

                $ba->push(
                    $this->html->eventButton(
                            $this->eventName('beginSelect'),
                            $this->_('Select')
                        )
                        ->setIcon('tick')
                        ->setDisposition('positive')
                        ->shouldValidate(false)
                );
            }
        }

        $fa->push($ba);
    }


    protected function _renderOverlaySelector(aura\html\widget\FieldArea $fa) {
        $this->_renderInlineDetails($fa);
        $label = $fa->getLabelBody();

        $ol = $fa->addOverlay($label);
        $fs = $ol->addFieldSet($this->_('Select'));



        // Search
        $fs->addFieldArea($this->_('Search'))->push(
            $this->html->textbox(
                    $this->fieldName('search'), 
                    $this->values->search
                )
                ->isRequired($this->_isRequired && !$this->hasSelection()),

            $this->html->eventButton(
                    $this->eventName('search'), 
                    $this->_('Search')
                )
                ->shouldValidate(false)
                ->setIcon('search')
        );


        
        // Show selected
        if(!$this->_isForMany) {
            $this->_renderOneSelected($fs);
        } else {
            $this->_renderManySelected($fs);
        }



        // Show search results
        if(!$this->values->searchResults->isEmpty()) {
            $searchResults = $this->_fetchResultList($this->values->searchResults->toArray());
            $searchResults = $this->_normalizeQueryResult($searchResults);

            if($this->_isQueryResultEmpty($searchResults)) {
                unset($this->values->searchResults);
            } else {
                $fa = $fs->addFieldArea($this->_('Search results'));

                foreach($searchResults as $result) {
                    $id = $this->_getResultId($result);
                    $name = $this->_getResultDisplayName($result);

                    if(!$this->_isForMany) {
                        $tickWidget = $this->html->radioButton(
                            $this->fieldName('selected'),
                            $this->isSelected($id),
                            $name,
                            $id
                        );
                    } else {
                        $tickWidget = $this->html->checkbox(
                            $this->fieldName('selected['.$id.']'),
                            $this->isSelected($id),
                            $name,
                            $id
                        );
                    }

                    $fa->push(
                        $this->html->hidden($this->fieldName('searchResults[]'), $id),
                        $tickWidget,
                        $this->html->string('<br />')
                    );
                }

                $fa->addEventButton(
                        $this->eventName('select'),
                        !$this->_isForMany ? $this->_('Set selected') : $this->_('Add selected')
                    )
                    ->shouldValidate(false)
                    ->setIcon('add');
            }
        }


        // Overlay buttons
        $ba = $fs->addButtonArea()->push(
            $this->html->eventButton(
                    $this->eventName('endSelect'),
                    $this->_('Done')
                )
                ->setIcon('tick')
                ->setDisposition('positive')
                ->shouldValidate(false)
        );

        if($this->_state->hasStore('originalSelection')) {
            $ba->push(
                $this->html->eventButton(
                        $this->eventName('reset'),
                        $this->_('Reset')
                    )
                    ->setIcon('refresh')
                    ->setDisposition('informative')
                    ->shouldValidate(false)
            );
        }

        $ba->push(
            $this->html->eventButton(
                    $this->eventName('cancelSelect'),
                    $this->_('Cancel')
                )
                ->setIcon('cancel')
                ->shouldValidate(false)
        );
    }

    protected function _renderOneSelected($fs) {
        if($this->values->selected->hasValue()) {
            $selectList = $this->_fetchResultList([$this->values['selected']]);
            $selected = $this->_extractQueryResult($selectList);

            if(!$selected) {
                unset($this->values->selected);
            } else {
                $fa = $fs->addFieldArea($this->_('Selected'));

                $id = $this->_getResultId($selected);
                $name = $this->_getResultDisplayName($selected);

                $fa->push(
                    $this->html->hidden($this->fieldName('selected'), $id),

                    $name,

                    $this->html->eventButton(
                            $this->eventName('clear'), 
                            $this->_('Remove')
                        )
                        ->shouldValidate(false)
                        ->setIcon('remove'),

                    $this->html->string('<br />')
                );
            }
        }
    }

    protected function _renderManySelected($fs) {
        if(!$this->values->selected->isEmpty()) {
            $selectedList = $this->_fetchResultList($this->values->selected->getKeys());
            $selectedList = $this->_normalizeQueryResult($selectedList);

            if($this->_isQueryResultEmpty($selectedList)) {
                unset($this->values->selected);
            } else {
                $fa = $fs->addFieldArea($this->_('Selected'));

                foreach($selectedList as $result) {
                    $id = $this->_getResultId($result);
                    $name = $this->_getResultDisplayName($result);

                    $fa->push(
                        $this->html->hidden($this->fieldName('selected['.$id.']'), $id),

                        $name,

                        $this->html->eventButton(
                                $this->eventName('remove', $id), 
                                $this->_('Remove')
                            )
                            ->shouldValidate(false)
                            ->setIcon('remove'),

                        $this->html->string('<br />')
                    );
                }
            }
        }
    }



// Fetching
    protected function _fetchSelectionList() {
        if($this->_isForMany) {
            return $this->_fetchResultList($this->values->selected->getKeys());
        } else {
            return $this->_fetchResultList([$this->values['selected']]);
        }
    }

    abstract protected function _fetchResultList(array $ids);
    abstract protected function _getSearchResultIdList($search, array $selected);



// Events
    protected function _onBeginSelectEvent() {
        if($this->_state->getStore('mode', 'details') == 'details') {
            $this->_state->setStore('originalSelection', $this->getSelected());
            $this->_state->setStore('mode', 'select');
        }
    }

    protected function _onCancelSelectEvent() {
        if($this->_state->getStore('mode') == 'select') {
            if($this->_state->hasStore('originalSelection')) {
                $this->setSelected($this->_state->getStore('originalSelection'));
            }

            $this->_state->setStore('mode', 'details');
        }
    }

    protected function _onEndSelectEvent() {
        if($this->_state->getStore('mode') == 'select') {
            $this->_state->removeStore('originalSelection');
            $this->_state->setStore('mode', 'details');
        }
    }


    protected function _onSearchEvent() {
        unset($this->values->searchResults);

        $search = $this->data->newValidator()
            ->addField('search', 'text')
                ->setSanitizer(function($value) {
                    if(empty($value)) {
                        $value = '*';
                    }

                    return $value;
                })
                ->end()
            ->validate($this->values)
            ->getValue('search');


        if($this->values->search->isValid()) {
            $this->values->searchResults = $this->_getSearchResultIdList($search, (array)$this->getSelected());
        }
    }


    protected function _onResetEvent() {
        if($this->_state->hasStore('originalSelection')) {
            $this->setSelected($this->_state->getStore('originalSelection'));
        }
    }

    protected function _onSelectEvent() {
        unset($this->values->search, $this->values->searchResults);
    }

    protected function _onClearEvent() {
        unset($this->values->selected);
    }

    protected function _onRemoveEvent($id) {
        unset($this->values->selected->{$id});
    }



// Selection
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
                $this->values->search->addError('required', $this->_(
                    'You must select at least one entry'
                ));
            }
        }

        return $this->getSelected();
    }
}