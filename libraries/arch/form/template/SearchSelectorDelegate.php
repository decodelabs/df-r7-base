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
    arch\form\IInlineFieldRenderableSelectorDelegate,
    arch\form\IDependentDelegate {

    use arch\form\TForm_InlineFieldRenderableSelectorDelegate;
    use arch\form\TForm_ValueListSelectorDelegate;
    use arch\form\TForm_DependentDelegate;

    protected $_searchMessage = null;
    protected $_searchPlaceholder = null;

    protected function _renderOverlaySelectorContent(aura\html\widget\Overlay $ol) {
        $fs = $ol->addFieldSet($this->_('Select'));

        // Search
        $fs->addFieldArea($this->_('Search'))->setDescription($this->_searchMessage)->push(
            $this->html->textbox(
                    $this->fieldName('search'), 
                    $this->values->search
                )
                ->setPlaceholder($this->_searchPlaceholder)
                ->isRequired($this->_isRequired && !$this->hasSelection())
                ->setFormEvent($this->eventName('search')),

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
    abstract protected function _getSearchResultIdList($search, array $selected);



// Events
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

    protected function _onSelectEvent() {
        unset($this->values->search, $this->values->searchResults);
    }
}