<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\node\form;

use df;
use df\core;
use df\arch;
use df\aura;
use df\opal;
use df\mesh;

abstract class SelectorDelegate extends Delegate implements
    arch\node\IInlineFieldRenderableModalSelectorDelegate,
    arch\node\IDependentDelegate {

    use arch\node\TForm_ModalDelegate;
    use arch\node\TForm_InlineFieldRenderableDelegate;
    use arch\node\TForm_SelectorDelegate;
    use arch\node\TForm_ValueListSelectorDelegate;
    use arch\node\TForm_DependentDelegate;

    const DEFAULT_MODES = [
        'select' => 'createOverlaySelectorUi',
        'details' => 'createInlineDetailsUi'
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
            return [];
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


    public function getSourceEntityLocator() {
        $adapter = $this->_getBaseQuery()->getSource()->getAdapter();

        if(!$adapter instanceof mesh\entity\ILocatorProvider) {
            throw new mesh\entity\RuntimeException(
                'Selector source is not an entity locator provider'
            );
        }

        return $adapter->getEntityLocator();
    }

    public function getSelectionName() {
        if($this->_isForMany) {
            return null;
        }

        $list = $this->_fetchSelectionList();

        if(!empty($list) && ($result = array_shift($list))) {
            return $this->_getResultDisplayName($result);
        }
    }


// Form
    protected function setDefaultValues() {
        $parts = explode('.', $this->_delegateId);
        $id = array_pop($parts);

        if(isset($this->request[$id])) {
            $this->setSelected($this->request[$id]);
        } else if($this->_isRequired) {
            $r = $this->_getQuery(['@primary'])->limit(2)->toList('@primary');

            if(count($r) == 1) {
                $this->setSelected(array_shift($r));
            }
        }
    }


// Render
    public function renderFieldContent(aura\html\widget\Field $fa) {
        $fa->setId($this->elementId('selector'));
        $fa->isRequired($this->_isRequired);

        $this->createModeUi([$fa]);
    }

    protected function createInlineDetailsUi(aura\html\widget\Field $fa) {
        $fa->addClass('delegate-selector');

        if($this instanceof arch\node\IDependentDelegate) {
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

        if($count > 0 && $count <= $threshold) {
            $this->_renderInlineListDetails($fa);
        } else {
            $this->_renderInlineTextDetails($fa);
        }
    }


    protected function _renderInlineListDetails(aura\html\widget\Field $fa) {
        $options = $this->_getOptionsList();
        $selected = $this->_fetchSelectionList();

        if($this->_isForMany) {
            $select = $this->html('div.w-checkboxGroup', function() use($options) {
                foreach($options as $key => $val) {
                    yield $this->html->checkbox(
                            $this->fieldName('selected['.$key.']'),
                            $this->values->selected->{$key},
                            $val,
                            $key
                        );
                }
            });
        } else {
            $select = $this->html->selectList(
                    $this->fieldName('selected'),
                    $this->values->selected,
                    $options
                )
                ->isRequired($this->_isRequired);
        }

        $fa->push(
            $this->html('div.w-selection', [
                $this->html('div.body', $select),
                $this->html->hidden($this->fieldName('_poke'), 1),

                $ba = $this->html->buttonArea()
            ])
        );

        $this->_renderDetailsButtonGroup($ba, $selected, true);
    }

    protected function _renderInlineTextDetails(aura\html\widget\Field $fa) {
        $fa->push($this->html('<div class="w-selection"><div class="body">'));
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

    protected function _renderDetailsButtonGroup(aura\html\widget\ButtonArea $ba, $selected, $isList=false) {
        if($isList) {
            $ba->push(
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
                    ->shouldValidate(false)
            );
        } else if(empty($selected)) {
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
                    ->shouldValidate(false)
            );
        }


        if(!empty($selected)) {
            $ba->push(
                $this->html->eventButton(
                        $this->eventName('clear'),
                        $this->_('Clear')
                    )
                    ->setIcon('remove')
                    ->shouldValidate(false)
            );
        }
    }

    protected function createOverlaySelectorUi(aura\html\widget\Field $fa) {
        $this->createInlineDetailsUi($fa);

        $selected = $this->_fetchSelectionList();

        if(!$this->_isForMany && $selected) {
            $selected = array_shift($selected);
        }

        if(empty($selected)) {
            $selected = null;
        }

        $ol = $fa->addOverlay($fa->getLabelBody());
        $this->createOverlaySelectorUiContent($ol, $selected);
    }

    protected function createOverlaySelectorUiContent(aura\html\widget\Overlay $ol, $selected) {
        $fs = $ol->addFieldSet($this->_('Select'))->addClass('stacked');

        if(!$this->values->search->hasValue() && $this->_defaultSearchString !== null) {
            $this->values->search->setValue($this->_defaultSearchString);
            $this->onSearchEvent();
        }

        // Search
        $fs->addField()->setDescription(
            $this->_searchMessage
        )->push(
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

            $keyList = [];

            if(!empty($selected)) {
                if(!$this->_isForMany) {
                    $selected = [$selected];
                }

                foreach($selected as $entry) {
                    $keyList[] = $this->_getResultId($entry);
                }
            }

            $query = $this->_getQuery(null, $search);
            $query->wherePrerequisite('@primary', '!in', $keyList);
            $query->paginate()->setDefaultLimit(50)->applyWith([]);

            $fa = $fs->addField($this->_('Search results'));
            $collectionWidget = $this->_renderCollectionList($query);

            if($collectionWidget instanceof aura\html\widget\IWidgetProxy) {
                $collectionWidget = $collectionWidget->toWidget();
            }

            if($collectionWidget instanceof aura\html\widget\IMappedListWidget) {
                // Collection list
                if($search !== null) {
                    $collectionWidget->addFieldAtIndex(0, 'relevance', function($record) {
                        return $this->html->progressBar(@$record['relevance'] * 100);
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

        $fa = $fs->addField($this->_('Selected'));
        $fa->addClass('delegate-selector');

        $id = $this->_getResultId($selected);
        $name = $this->_getResultDisplayName($selected);

        $fa->push(
            $this->html('div.w-selection', [
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

        $fa = $fs->addField($this->_('Selected'));
        $fa->addClass('delegate-selector');

        foreach($selected as $result) {
            $id = $this->_getResultId($result);
            $name = $this->_getResultDisplayName($result);

            $fa->push(
                $this->html('div.w-selection', [
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
    protected function onSearchEvent() {
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

    protected function onSelectEvent() {
        unset($this->values->search, $this->values->searchResults);
    }

    protected function onSelectAllEvent() {
        if(!$this->_isForMany) {
            return;
        }

        $selected = $this->getSelected();
        $query = $this->_getQuery(['@primary'], $this->values['search']);

        foreach($query->toList('@primary') as $key) {
            $selected[(string)$key] = (string)$key;
        }

        $this->setSelected($selected);
        unset($this->values->search, $this->values->searchResults);

        $this->onEndSelectEvent();
    }

    protected function onBeginSelectEvent() {
        $this->switchMode('details', 'select', function() {
            $this->_state->setStore('originalSelection', $this->getSelected());
        });
    }

    protected function onCancelSelectEvent() {
        $this->switchMode('select', 'details', function() {
            if($this->_state->hasStore('originalSelection')) {
                $this->setSelected($this->_state->getStore('originalSelection'));
            }
        });

        return $this->http->redirect('#'.$this->elementId('selector'));
    }

    protected function onEndSelectEvent() {
        $this->switchMode('select', 'details', function() {
            $this->_state->removeStore('originalSelection');
        });

        return $this->http->redirect('#'.$this->elementId('selector'));
    }

    protected function onResetEvent() {
        if($this->_state->hasStore('originalSelection')) {
            $this->setSelected($this->_state->getStore('originalSelection'));
        }
    }
}