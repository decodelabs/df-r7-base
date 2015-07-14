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
    
abstract class SelectorDelegate extends arch\form\Delegate implements 
    arch\form\IInlineFieldRenderableModalSelectorDelegate,
    arch\form\IDependentDelegate {

    use arch\form\TForm_ModalDelegate;
    use arch\form\TForm_InlineFieldRenderableDelegate;
    use arch\form\TForm_SelectorDelegate;
    use arch\form\TForm_ValueListSelectorDelegate;
    use arch\form\TForm_DependentDelegate;

    protected static $_defaultModes = [
        'select' => '_renderOverlaySelector',
        'details' => '_renderInlineDetails'
    ];

    protected $_searchMessage = null;
    protected $_searchPlaceholder = null;
    protected $_defaultSearchString = null;

    private $_selectionList = null;

    public function setSearchMessage($message) {
        $this->_searchMessage = $message;
        return $this;
    }

    public function getSearchMessage() {
        return $this->_searchMessage;
    }

    public function setSearchPlaceholder($placeholder) {
        $this->_searchPlaceholder = $placeholder;
        return $this;
    }

    public function getSearchPlaceholder() {
        return $this->_searchPlaceholder;
    }

    public function setDefaultSearchString($search) {
        $this->_defaultSearchString = $search;
        return $this;
    }

    public function getDefaultSearchString() {
        return $this->_defaultSearchString;
    }


// Modes
    protected function _getDefaultMode() {
        return 'details';
    }



// Queries
    abstract protected function _getBaseQuery($fields=null);

    protected function _getQuery($fields=null, $search=null) {
        $query = $this->_getBaseQuery($fields);
        $this->applyFilters($query);

        if($search !== null) {
            $this->_applyQuerySearch($query, $search);
        }

        return $query;
    }

    protected function _applyQuerySearch(opal\query\IQuery $query, $search) {
        $query->searchFor($search);
    }

    protected function _fetchSelectionList($cache=true) {
        if($cache && $this->_selectionList !== null) {
            return $this->_selectionList;
        }

        $selected = $this->getSelected();

        if(empty($selected)) {
            return $selected;
        }

        if(!$this->_isForMany) {
            $selected = [$selected];
        }

        $output = $this->_getQuery()
            ->where('@primary', 'in', $selected)
            ->toArray();

        if($cache) {
            $this->_selectionList = $output;
        }

        return $output;
    }

    protected function _getResultId($result) {
        return $result['id'];
    }

    protected function _getResultDisplayName($result) {
        return $result['name'];
    }

    protected function _countTotalItems() {
        return $this->_getQuery()->count();
    }

    protected function _getOptionsList() {
        $output = [];
        $query = $this->_getQuery()
            ->paginate()
                ->setDefaultLimit(100)
                ->applyWith([]);

        foreach($query as $row) {
            $output[(string)$this->_getResultId($row)] = $this->_getResultDisplayName($row);
        }

        return $output;
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
            $messages = $this->getDependencyMessages();

            if(!empty($messages)) {
                foreach($messages as $key => $value) {
                    $fa->addFlashMessage($value, 'warning');
                }
                return;
            }
        }

        if($messages = $this->_getSelectionErrors()) {
            $fa->push($this->html->fieldError($messages));
        }

        $count = $this->_countTotalItems();
        $threshold = $this->_isForMany ? 20 : 70;

        if($count <= $threshold) {
            $this->_renderInlineListDetails($fa);
        } else {
            $this->_renderInlineTextDetails($fa);
        }
    }


    protected function _renderInlineListDetails(aura\html\widget\FieldArea $fa) {
        $options = $this->_getOptionsList();

        $type = $this->_isForMany ? 'checkboxGroup' : 'selectList';
        $select = $this->html->{$type}(
                $this->fieldName('selected'),
                $this->values->selected,
                $options
            )
            ->isRequired($this->_isRequired);

        $fa->push(
            $this->html('div.widget-selection', [
                $this->html('div.body', $select),

                $this->html->buttonArea(
                    $this->html->eventButton(
                            $this->eventName('beginSelect'),
                            $this->_('Search')
                        )
                        ->setIcon('search')
                        ->setDisposition('positive')
                        ->shouldValidate(false),

                    $this->html->eventButton(
                            $this->eventName('endSelect'),
                            $this->_('Update')
                        )
                        ->setIcon('refresh')
                        ->setDisposition('informative')
                        ->shouldValidate(false),

                    $this->html->eventButton(
                            $this->eventName('clear'), 
                            $this->_('Clear')
                        )
                        ->shouldValidate(false)
                        ->setIcon('remove')
                )
            ])
        );
    }

    protected function _renderInlineTextDetails(aura\html\widget\FieldArea $fa) {
        $fa->push($this->html('<div class="widget-selection"><div class="body">'));
        $selected = $this->_fetchSelectionList();

        if($this->_isForMany) {
            // Multiple entry
            if(empty($selected)) {
                $fa->push(
                    $this->html('em', $this->_('nothing selected')),
                    $this->html('</div>')
                );
            } else {
                $tempList = $selected;
                $count = count($selected);
                $displayList = [];

                for($i = 0; $i < 3 && !empty($tempList); $i++) {
                    $count--;

                    $displayList[] = $this->html(
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

                $fa->push(
                    $this->html->bulletList($displayList),
                    $this->html('</div>')
                );

                foreach($selected as $row) {
                    $id = $this->_getResultId($row);

                    $fa->push(
                        $this->html->hidden(
                            $this->fieldName('selected['.$id.']'), 
                            $id
                        )
                    );
                }
            }
        } else {
            // Single entry
            if(!empty($selected)) {
                // Selection made
                $selected = array_shift($selected);

                $resultId = $this->_getResultId($selected);
                $resultName = $this->_getResultDisplayName($selected);

                $fa->push(
                    $this->html('strong', $resultName),

                    $this->html->hidden(
                            $this->fieldName('selected'),
                            $resultId
                        )
                );
            } else {
                // No selection

                $fa->push(
                    $this->html('em', $this->_('nothing selected'))
                );
            }

            $fa->push($this->html('</div>'));
        }

        $ba = $fa->addButtonArea();
        $this->_renderDetailsButtonGroup($ba, $selected);

        $fa->push($this->html('</div>'));
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

        $selected = $this->_fetchSelectionList();

        if(!$this->_isForMany && $selected) {
            $selected = array_shift($selected);
        }

        $ol = $fa->addOverlay($fa->getLabelBody());
        $fs = $ol->addFieldSet($this->_('Select'));

        if(!$this->values->search->hasValue() && $this->_defaultSearchString !== null) {
            $this->values->search->setValue($this->_defaultSearchString);
            $this->_onSearchEvent();
        }

        // Search
        $fs->addFieldArea()->setDescription($this->_searchMessage)->push(
            $this->html->textbox(
                    $this->fieldName('search'), 
                    $this->values->search
                )
                ->setPlaceholder($this->_searchPlaceholder)
                //->isRequired($this->_isRequired && !$this->hasSelection())
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
            $this->_renderOneSelected($fs, $selected);
        } else {
            $this->_renderManySelected($fs, $selected);
        }



        // Show search results
        if(strlen($search = $this->values['search'])) {
            if($search == '*' || $search == '?') {
                $search = null;
            }

            $query = $this->_getQuery(null, $search);
            $query->paginate()->setDefaultLimit(50)->applyWith([]);
            
            $fa = $fs->addFieldArea($this->_('Search results'));
            $collectionWidget = $this->_renderCollectionList($query);

            if($collectionWidget instanceof aura\html\widget\IWidgetProxy) {
                $collectionWidget = $collectionWidget->toWidget();
            }
            
            if($collectionWidget instanceof aura\html\widget\IMappedListWidget) {
                // Collection list
                if($search !== null) {
                    $collectionWidget->addFieldAtIndex(0, 'relevance', function($record) {
                        return $this->html->progressBar($record['relevance'] * 100);
                    });
                }

                $collectionWidget->addFieldAtIndex(0, 'select', 'x', function($row) {
                    $id = $this->_getResultId($row);

                    return [
                        $this->_renderCheckbox($id),
                        $this->html->hidden($this->fieldName('searchResults[]'), $id)
                    ];
                });

                $fa->push($collectionWidget);
            } else if($collectionWidget !== null) {
                // Something else - trust child class
                $fa->push($collectionWidget);
            } else {
                // Fallback to standard
                foreach($query as $result) {
                    $id = $this->_getResultId($result);
                    $name = $this->_getResultDisplayName($result);

                    $fa->push(
                        $this->html->hidden($this->fieldName('searchResults[]'), $id),
                        $this->_renderCheckbox($id, $name),
                        $this->html('<br />')
                    );
                }
            }

            

            $fa->addEventButton(
                    $this->eventName('select'),
                    !$this->_isForMany ? $this->_('Set selected') : $this->_('Add selected')
                )
                ->shouldValidate(false)
                ->setIcon('add');

            if($this->_isForMany) {
                $fa->addEventButton(
                        $this->eventName('selectAll'),
                        $this->_('Add all matches')
                    )
                    ->shouldValidate(false)
                    ->setDisposition('positive')
                    ->setIcon('star');
            }
        }


        // Overlay buttons
        $ba = $fs->addButtonArea()->push(
            $this->html->eventButton(
                    $this->eventName('endSelect'),
                    $this->_('Done')
                )
                ->setIcon('select')
                ->setDisposition('positive')
                ->shouldValidate(false)
        );

        $bg = $ba->addButtonGroup();

        if($this->_state->hasStore('originalSelection')) {
            $bg->push(
                $this->html->resetEventButton($this->eventName('reset'))
            );
        }

        $bg->push(
            $this->html->cancelEventButton($this->eventName('cancelSelect'))
        );
    }

    protected function _renderOneSelected($fs, $selected) {
        if($selected === null) {
            return;
        }

        $fa = $fs->addFieldArea($this->_('Selected'));
        $fa->addClass('delegate-selector');

        $id = $this->_getResultId($selected);
        $name = $this->_getResultDisplayName($selected);

        $fa->push(
            $this->html('div.widget-selection', [
                $this->html->hidden($this->fieldName('selected'), $id),

                $this->html('div.body', $name),

                $this->html->buttonArea(
                    $this->html->eventButton(
                            $this->eventName('clear'), 
                            $this->_('Remove')
                        )
                        ->shouldValidate(false)
                        ->setIcon('remove')
                )
            ])
        );
    }

    protected function _renderManySelected($fs, $selected) {
        if(empty($selected)) {
            return;
        }

        $fa = $fs->addFieldArea($this->_('Selected'));
        $fa->addClass('delegate-selector');

        foreach($selected as $result) {
            $id = $this->_getResultId($result);
            $name = $this->_getResultDisplayName($result);

            $fa->push(
                $this->html('div.widget-selection', [
                    $this->html->hidden($this->fieldName('selected['.$id.']'), $id),
                    
                    $this->html('div.body', $name),

                    $this->html->buttonArea(
                        $this->html->eventButton(
                                $this->eventName('remove', $id), 
                                $this->_('Remove')
                            )
                            ->shouldValidate(false)
                            ->setIcon('remove')
                    )
                ])
            );
        }
    }


    protected function _renderCollectionList($result) {
        return null;
    }

    protected function _renderCheckbox($id, $name=null) {
        if(!$this->_isForMany) {
            return $this->html->radioButton(
                $this->fieldName('selected'),
                $this->isSelected($id),
                $name,
                $id
            );
        } else {
            return $this->html->checkbox(
                $this->fieldName('selected['.$id.']'),
                $this->isSelected($id),
                $name,
                $id
            );
        }
    }


// Events
    protected function _onSearchEvent() {
        unset($this->values->searchResults);

        $this->data->newValidator()
            // Search
            ->addField('search', 'text')
                ->setSanitizer(function($value) {
                    if(empty($value)) {
                        $value = '*';
                    }

                    return $value;
                })
                
            ->validate($this->values)
            ->getValue('search');
    }

    protected function _onSelectEvent() {
        unset($this->values->search, $this->values->searchResults);
    }

    protected function _onSelectAllEvent() {
        if(!$this->_isForMany) {
            return;
        }

        $this->setSelected($this->values->searchResults->toArray());
        unset($this->values->search, $this->values->searchResults);

        $this->_onEndSelectEvent();
    }

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