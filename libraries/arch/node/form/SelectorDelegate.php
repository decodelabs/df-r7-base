<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\node\form;

use DecodeLabs\Exceptional;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Tagged as Html;
use df\arch;
use df\arch\IComponent as Component;

use df\aura;
use df\core;

use df\mesh;
use df\opal;
use df\opal\query\ISelectQuery as SelectQuery;

abstract class SelectorDelegate extends Delegate implements
    arch\node\IInlineFieldRenderableModalSelectorDelegate,
    arch\node\IDependentDelegate
{
    use arch\node\TForm_ModalDelegate;
    use arch\node\TForm_InlineFieldRenderableDelegate;
    use arch\node\TForm_SelectorDelegate;
    use arch\node\TForm_ValueListSelectorDelegate;
    use arch\node\TForm_DependentDelegate;

    public const DEFAULT_MODES = [
        'select' => 'createOverlaySelectorUi',
        'create' => 'createOverlayCreateUi',
        'details' => 'createInlineDetailsUi'
    ];

    public const ONE_LIST_THRESHOLD = 70;
    public const MANY_LIST_THRESHOLD = 20;

    protected $_searchMessage = null;
    protected $_searchPlaceholder = null;
    protected $_defaultSearchString = null;
    protected $_autoSelect = true;

    private $_selectionList = null;

    public function setSearchMessage($message)
    {
        $this->_searchMessage = $message;
        return $this;
    }

    public function getSearchMessage()
    {
        return $this->_searchMessage;
    }

    public function setSearchPlaceholder($placeholder)
    {
        $this->_searchPlaceholder = $placeholder;
        return $this;
    }

    public function getSearchPlaceholder()
    {
        return $this->_searchPlaceholder;
    }

    public function setDefaultSearchString($search)
    {
        $this->_defaultSearchString = $search;
        return $this;
    }

    public function getDefaultSearchString()
    {
        return $this->_defaultSearchString;
    }

    public function shouldAutoSelect(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_autoSelect = $flag;
            return $this;
        }

        return $this->_autoSelect;
    }


    // Modes
    protected function _getDefaultMode(): string
    {
        return 'details';
    }



    // Queries
    abstract protected function getBaseQuery(array $fields = null): SelectQuery;

    protected function getQuery(?array $fields = null, ?string $search = null): SelectQuery
    {
        $query = $this->getBaseQuery($fields);
        $this->applyFilters($query);

        if ($search !== null) {
            $this->applyQuerySearch($query, $search);
        }

        return $query;
    }

    protected function applyQuerySearch(opal\query\ISelectQuery $query, string $search): void
    {
        if ($query instanceof opal\query\ISearchableQuery) {
            $query->searchFor($search);
        }
    }

    protected function _fetchSelectionList($cache = true)
    {
        if ($cache && $this->_selectionList !== null) {
            return $this->_selectionList;
        }

        $selected = $this->getSelected();

        if (empty($selected)) {
            return [];
        }

        if (!$this->_isForMany) {
            $selected = [$selected];
        }

        $rowSet = $this->getQuery()
            ->where('@primary', 'in', $selected)
            ->toArray();

        $rows = [];

        foreach ($rowSet as $row) {
            $id = $this->getResultId($row);
            $rows[$id] = $row;
        }

        $output = [];

        foreach ($selected as $id) {
            if (isset($rows[$id])) {
                $output[] = $rows[$id];
            }
        }

        if ($cache) {
            $this->_selectionList = $output;
        }

        return $output;
    }

    protected function getResultId(array $row): string
    {
        return (string)$row['id'];
    }

    protected function getResultDisplayName(array $result)
    {
        return $result['name'];
    }

    protected function _countTotalItems()
    {
        return $this->getQuery()->count();
    }

    protected function _getOptionsList()
    {
        $output = [];
        $query = $this->getQuery()
            ->paginate()
                ->setDefaultLimit(100)
                ->applyWith([]);

        foreach ($query as $row) {
            $output[$this->getResultId($row)] = $this->getResultDisplayName($row);
        }

        return $output;
    }


    public function getSourceEntityLocator(): mesh\entity\ILocator
    {
        $adapter = $this->getBaseQuery()->getSource()->getAdapter();

        if (!$adapter instanceof mesh\entity\ILocatorProvider) {
            throw Exceptional::{'df/mesh/entity/Runtime'}(
                'Selector source is not an entity locator provider'
            );
        }

        return $adapter->getEntityLocator();
    }

    public function getSelectionName()
    {
        if ($this->_isForMany) {
            return null;
        }

        $list = $this->_fetchSelectionList();

        if (!empty($list) && ($result = array_shift($list))) {
            return $this->getResultDisplayName($result);
        }
    }


    // Form
    protected function setDefaultValues(): void
    {
        $parts = explode('.', $this->_delegateId);
        $id = array_pop($parts);

        if (isset($this->request[$id]) && !isset($this->values->selected)) {
            $this->setSelected($this->request[$id]);
        } elseif ($this->_isRequired && $this->_autoSelect) {
            $r = $this->getQuery(['@primary'])->limit(2)->toList('@primary');

            if (count($r) == 1) {
                $this->setSelected(array_shift($r));
            }
        }
    }


    // Render
    public function renderFieldContent(aura\html\widget\Field $fa): void
    {
        $fa->setId($this->elementId('selector'));
        $fa->isRequired($this->_isRequired);

        $this->createModeUi([$fa]);
    }

    protected function createInlineDetailsUi(aura\html\widget\Field $fa)
    {
        $fa->addClass('delegate-selector');

        if ($this instanceof arch\node\IDependentDelegate) {
            $messages = $this->getDependencyMessages();

            if (!empty($messages)) {
                foreach ($messages as $key => $value) {
                    $fa->addFlashMessage($value, 'warning');
                }
                return;
            }
        }

        /*
        if($messages = $this->_getSelectionErrors()) {
            $fa->push($this->html->fieldError($messages));
        }
         */

        $count = $this->_countTotalItems();
        $threshold = $this->_isForMany ?
            static::MANY_LIST_THRESHOLD :
            static::ONE_LIST_THRESHOLD;

        if ($count > 0 && ($count <= $threshold || (!$this->hasSelection() && !$this->_isForMany))) {
            $this->_renderInlineListDetails($fa, $count);
        } else {
            $this->_renderInlineTextDetails($fa, $count);
        }
    }


    protected function _renderInlineListDetails(aura\html\widget\Field $fa, int $count = 0)
    {
        $options = $this->_getOptionsList();
        $selected = $this->_fetchSelectionList();
        $threshold = $this->_isForMany ?
            static::MANY_LIST_THRESHOLD :
            static::ONE_LIST_THRESHOLD;

        if ($this->_isForMany) {
            $select = Html::{'div.w.list.checkbox'}(function () use ($options) {
                foreach ($options as $key => $val) {
                    yield $this->html->checkbox(
                        $this->fieldName('selected[' . $key . ']'),
                        $this->values->selected->{$key},
                        $val,
                        $key
                    );
                }
            });
        } else {
            if (count($options) > $threshold) {
                $options = array_slice($options, 0, $threshold, true);
            }

            $select = $this->html->select(
                $this->fieldName('selected'),
                $this->values->selected,
                $options
            )
                //->isRequired($this->_isRequired)
            ;
        }

        $fa->push(
            Html::{'div.w.list.selection'}([
                Html::{'div.body'}($select),
                $this->html->hidden($this->fieldName('_poke'), 1),

                $ba = $this->html->buttonArea()
            ])
        );

        $this->_renderDetailsButtonGroup($ba, $selected, true);
    }

    protected function _renderInlineTextDetails(aura\html\widget\Field $fa, int $count = 0)
    {
        $fa->push(Html::raw('<div class="w list selection"><div class="body">'));
        $selected = $this->_fetchSelectionList();

        if ($this->_isForMany) {
            // Multiple entry
            if (empty($selected)) {
                $fa->push(
                    Html::{'em'}($count ? $this->_('nothing selected') : $this->_('nothing available')),
                    Html::raw('</div>')
                );
            } else {
                /** @var array $tempList */
                $tempList = $selected;
                $count = count($selected);
                $displayList = [];

                for ($i = 0; $i < 3 && !empty($tempList); $i++) {
                    $count--;

                    $displayList[] = Html::{'strong'}(
                        $this->getResultDisplayName(array_shift($tempList))
                    );
                }

                if ($count) {
                    $displayList[] = $this->html->_(
                        'and <strong>%c%</strong> more selected',
                        ['%c%' => $count]
                    );
                }

                $fa->push(
                    Html::uList($displayList),
                    Html::raw('</div>')
                );

                foreach ($selected as $row) {
                    $id = $this->getResultId($row);

                    $fa->push(
                        $this->html->hidden(
                            $this->fieldName('selected[' . $id . ']'),
                            $id
                        )
                    );
                }
            }
        } else {
            // Single entry
            if (!empty($selected)) {
                // Selection made
                $selected = array_shift($selected);

                $resultId = $this->getResultId($selected);
                $resultName = $this->getResultDisplayName($selected);

                $fa->push(
                    Html::{'strong'}($resultName),
                    $this->html->hidden(
                        $this->fieldName('selected'),
                        $resultId
                    )
                );
            } else {
                // No selection
                $fa->push(
                    Html::{'em'}($this->_('nothing selected'))
                );
            }

            $fa->push(Html::raw('</div>'));
        }

        $ba = $fa->addButtonArea();
        $this->_renderDetailsButtonGroup($ba, $selected);

        $fa->push(Html::raw('</div>'));
    }

    protected function _renderDetailsButtonGroup(aura\html\widget\ButtonArea $ba, $selected, $isList = false)
    {
        if (method_exists($this, 'createOverlayCreateUiContent')) {
            $ba->push(
                $this->html->eventButton(
                    $this->eventName('beginCreate'),
                    $this->_('Add')
                )
                    ->setIcon('add')
                    ->setDisposition('positive')
                    ->shouldValidate(false)
            );
        }

        if ($isList) {
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
        } elseif (empty($selected)) {
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


        if (!empty($selected)) {
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

    protected function createOverlaySelectorUi(aura\html\widget\Field $fa)
    {
        $this->createInlineDetailsUi($fa);

        $selected = $this->_fetchSelectionList();

        if (!$this->_isForMany && $selected) {
            $selected = array_shift($selected);
        }

        if (empty($selected)) {
            $selected = null;
        }

        $ol = $fa->addOverlay($fa->getLabelBody());
        $this->createOverlaySelectorUiContent($ol, $selected);
    }

    protected function createOverlaySelectorUiContent(aura\html\widget\Overlay $ol, $selected)
    {
        $fs = $ol->addFieldSet($this->_('Select'));

        if (!$this->values->search->hasValue() && $this->_defaultSearchString !== null) {
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
        if (!$this->_isForMany) {
            $this->_renderOneSelected($fs, $selected);
        } else {
            $this->_renderManySelected($fs, $selected);
        }



        // Show search results
        if (strlen($search = $this->values['search'])) {
            if ($search == '*' || $search == '?') {
                $search = null;
            }

            $keyList = [];

            if (!empty($selected)) {
                if (!$this->_isForMany) {
                    $selected = [$selected];
                }

                foreach ($selected as $entry) {
                    $keyList[] = $this->getResultId($entry);
                }
            }

            $query = $this->getQuery(null, $search);
            $query->wherePrerequisite('@primary', '!in', $keyList);
            $query->paginate()->setDefaultLimit(50)->applyWith($this->values->paginator);

            $fa = $fs->addField($this->_('Search results'));

            foreach ($this->values->paginator as $key => $val) {
                $fa->addHidden($this->fieldName('paginator[' . $key . ']'), $val);
            }

            $collectionWidget = $this->renderCollectionList($query);

            if ($collectionWidget instanceof aura\html\widget\IWidgetProxy) {
                $collectionWidget = $collectionWidget->toWidget();
            }

            if ($collectionWidget instanceof aura\html\widget\IMappedListWidget) {
                // Collection list
                if ($search !== null) {
                    $collectionWidget->addFieldAtIndex(0, 'relevance', function ($record) {
                        return $this->html->progressBar(($record['relevance'] ?? 0) * 100);
                    });
                }

                $collectionWidget->addFieldAtIndex(0, 'select', 'x', function ($row) {
                    $id = $this->getResultId($row);

                    return [
                        $this->_renderCheckbox($id),
                        $this->html->hidden($this->fieldName('searchResults[]'), $id)
                    ];
                });

                if (method_exists($collectionWidget, 'setMode')) {
                    $collectionWidget
                        ->setMode('post')
                        ->setPostEvent($this->eventName('paginate'));
                }

                $fa->push($collectionWidget);
            } elseif ($collectionWidget !== null) {
                // Something else - trust child class
                $fa->push($collectionWidget);
            } else {
                // Fallback to standard
                foreach ($query as $result) {
                    $id = $this->getResultId($result);
                    $name = $this->getResultDisplayName($result);

                    $fa->push(
                        $this->html->hidden($this->fieldName('searchResults[]'), $id),
                        $this->_renderCheckbox($id, $name),
                        Html::{'br'}()
                    );
                }
            }


            $fa->addEventButton(
                $this->eventName('select'),
                !$this->_isForMany ? $this->_('Set selected') : $this->_('Add selected')
            )
                ->shouldValidate(false)
                ->setIcon('add');

            if ($this->_isForMany) {
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

        if ($this->_state->hasStore('originalSelection')) {
            $bg->push(
                $this->html->resetEventButton($this->eventName('reset'))
            );
        }

        $bg->push(
            $this->html->cancelEventButton($this->eventName('cancelSelect'))
        );
    }

    protected function createOverlayCreateUi(aura\html\widget\Field $fa)
    {
        $this->createInlineDetailsUi($fa);

        $ol = $fa->addOverlay($fa->getLabelBody());

        if (method_exists($this, 'createOverlayCreateUiContent')) {
            $this->createOverlayCreateUiContent($ol);
        }

        // Buttons
        $ol->addButtonArea(
            $this->html->eventButton(
                $this->eventName('create'),
                $this->_('Save')
            )
                ->setIcon('save')
                ->shouldValidate(false),
            $this->html->buttonGroup(
                $this->html->resetEventButton($this->eventName('resetCreate')),
                $this->html->cancelEventButton($this->eventName('cancelCreate'))
            )
        )->addClass('floated');
    }

    protected function _renderOneSelected($fs, $selected)
    {
        if ($selected === null) {
            return;
        }

        $fa = $fs->addField($this->_('Selected'));
        $fa->addClass('delegate-selector');

        $id = $this->getResultId($selected);
        $name = $this->getResultDisplayName($selected);

        $fa->push(
            Html::{'div.w.list.selection'}([
                $this->html->hidden($this->fieldName('selected'), $id),

                Html::{'div.body'}($name),

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

    protected function _renderManySelected($fs, $selected)
    {
        if (empty($selected)) {
            return;
        }

        $fa = $fs->addField($this->_('Selected'));
        $fa->addClass('delegate-selector');

        foreach ($selected as $result) {
            $id = $this->getResultId($result);
            $name = $this->getResultDisplayName($result);

            $fa->push(
                Html::{'div.w.list.selection'}([
                    $this->html->hidden($this->fieldName('selected[' . $id . ']'), $id),

                    Html::{'div.body'}($name),

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


    protected function renderCollectionList(?iterable $collection): ?Component
    {
        return null;
    }

    protected function _renderCheckbox($id, $name = null)
    {
        if (!$this->_isForMany) {
            return $this->html->radio(
                $this->fieldName('selected'),
                $this->isSelected($id),
                $name,
                $id
            );
        } else {
            return $this->html->checkbox(
                $this->fieldName('selected[' . $id . ']'),
                $this->isSelected($id),
                $name,
                $id
            );
        }
    }


    // Events
    protected function onSearchEvent()
    {
        unset($this->values->searchResults, $this->values->paginator);

        $this->data->newValidator()
            // Search
            ->addField('search', 'text')
                ->setSanitizer(function ($value) {
                    if (empty($value)) {
                        $value = '*';
                    }

                    return $value;
                })

            ->validate($this->values)
            ->getValue('search');
    }

    protected function onSelectEvent()
    {
        unset($this->values->search, $this->values->searchResults);
    }

    protected function onSelectAllEvent()
    {
        if (!$this->_isForMany) {
            return;
        }

        $selected = $this->getSelected();
        $query = $this->getQuery(['@primary'], $this->values['search']);

        foreach ($query->toList('@primary') as $key) {
            $selected[(string)$key] = (string)$key;
        }

        $this->setSelected($selected);
        unset($this->values->search, $this->values->searchResults);

        $this->onEndSelectEvent();
    }

    protected function onBeginSelectEvent()
    {
        $this->switchMode('details', 'select', function () {
            $this->_state->setStore('originalSelection', $this->getSelected());
        });
    }

    protected function onCancelSelectEvent()
    {
        $this->switchMode('select', 'details', function () {
            if ($this->_state->hasStore('originalSelection')) {
                $this->setSelected($this->_state->getStore('originalSelection'));
            }
        });

        return Legacy::$http->redirect('#' . $this->elementId('selector'));
    }

    protected function onEndSelectEvent()
    {
        $this->switchMode('select', 'details', function () {
            $this->_state->removeStore('originalSelection');
        });

        return Legacy::$http->redirect('#' . $this->elementId('selector'));
    }

    protected function onBeginCreateEvent()
    {
        $this->switchMode('details', 'create', function () {
            $this->_state->setStore('originalSelection', $this->getSelected());
        });
    }

    protected function onResetCreateEvent()
    {
    }

    protected function onCancelCreateEvent()
    {
        $this->switchMode('create', 'details', function () {
            if ($this->_state->hasStore('originalSelection')) {
                $this->setSelected($this->_state->getStore('originalSelection'));
            }
        });

        return Legacy::$http->redirect('#' . $this->elementId('selector'));
    }

    protected function onCreateEvent()
    {
        $id = $this->saveNewRecord();

        if ($this->values->isValid() && $id !== null) {
            $this->addSelected($id);
            $this->onResetCreateEvent();
            return $this->onEndCreateEvent();
        }
    }

    protected function saveNewRecord()
    {
    }

    protected function onEndCreateEvent()
    {
        $this->switchMode('create', 'details', function () {
            $this->_state->removeStore('originalSelection');
        });

        return Legacy::$http->redirect('#' . $this->elementId('selector'));
    }

    protected function onResetEvent(): mixed
    {
        if ($this->_state->hasStore('originalSelection')) {
            $this->setSelected($this->_state->getStore('originalSelection'));
        }

        return null;
    }

    protected function onPaginateEvent($query)
    {
        $query = core\collection\Tree::fromArrayDelimitedString($query);
        $this->values->paginator->import($query);
    }
}
