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
    
abstract class SearchSelectorDelegate extends arch\form\Delegate implements ISelectorDelegate {

    use TSelectorDelegate;
    use TSelectorDelegateQueryTools;

    public function renderFieldSet($fieldSetName) {
        $fs = $this->html->fieldSet($fieldSetName);

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

        return $fs;
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

    abstract protected function _fetchResultList(array $ids);

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

    abstract protected function _getSearchResultIdList($search, array $selected);

    protected function _onSelectEvent() {
        unset($this->values->search, $this->values->searchResults);
    }

    protected function _onClearEvent() {
        unset($this->values->selected);
    }

    protected function _onRemoveEvent($id) {
        unset($this->values->selected->{$id});
    }


    public function isSelected($id) {
        if(!$this->_isForMany) {
            return $this->values['selected'] == $id;
        } else {
            return $this->values->selected->has($id);
        }
    }

    public function setSelected($selected) {
        if(!$this->_isForMany) {
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
                    'You must selected at least one entry'
                ));
            }
        }

        return $this->getSelected();
    }
}