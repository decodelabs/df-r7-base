<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold;

use df\arch\scaffold\Index\Decorator as IndexDecorator;
use df\arch\scaffold\Index\DecoratorTrait as IndexDecoratorTrait;
use df\arch\scaffold\Record\DataProvider as RecordDataProvider;
use df\arch\scaffold\Record\DataProviderTrait as RecordDataProviderTrait;
use df\arch\scaffold\Record\Decorator as RecordDecorator;
use df\arch\scaffold\Record\DecoratorTrait as RecordDecoratorTrait;
use df\arch\scaffold\Record\FilterProvider as RecordFilterProvider;
use df\arch\scaffold\Record\FilterProviderTrait as RecordFilterProviderTrait;
use df\arch\scaffold\Section\Decorator as SectionDecorator;
use df\arch\scaffold\Section\DecoratorTrait as SectionDecoratorTrait;
use df\arch\scaffold\Section\Provider as SectionProvider;
use df\arch\scaffold\Section\ProviderTrait as SectionProviderTrait;

use df\opal\query\ISelectQuery as SelectQuery;

use DecodeLabs\Tagged\Html;
use DecodeLabs\Glitch\Dumpable;

abstract class RecordAdmin extends Generic implements
    IndexDecorator,
    RecordDataProvider,
    RecordDecorator,
    RecordFilterProvider,
    SectionDecorator,
    SectionProvider,
    Dumpable
{
    use IndexDecoratorTrait;
    use RecordDataProviderTrait;
    use RecordDecoratorTrait;
    use RecordFilterProviderTrait;
    use SectionDecoratorTrait;
    use SectionProviderTrait;

    const ADAPTER = null;
    const KEY_NAME = null;
    const ITEM_NAME = null;
    const URL_KEY = null;

    const ID_FIELD = 'id';
    const NAME_FIELD = null;

    const DEFAULT_SECTION = 'details';

    const CAN_ADD = true;
    const CAN_PREVIEW = false;
    const CAN_EDIT = true;
    const CAN_DELETE = true;
    const CONFIRM_DELETE = true;
    const DELETE_PERMANENT = true;
    const IS_PARENT = false;
    const IS_SHARED = false;

    const CAN_SEARCH = true;
    const CAN_SELECT = false;

    const SECTIONS = null;

    const DETAILS_FIELDS = null;
    const LIST_FIELDS = null;
    const SEARCH_FIELDS = null;


    // Nodes
    public function indexHtmlNode()
    {
        return $this->buildRecordListNode();
    }


    // Components
    public function renderRecordList(?callable $filter=null, array $fields=null, ?string $contextKey=null, bool $controls=true)
    {
        $queryMode = $this->request->getNode();
        $query = $this->queryRecordList($queryMode);

        if ($filter) {
            $filter($query);
        }

        if (static::CAN_SEARCH && $controls) {
            $search = $this->request->getQueryTerm('search');

            if (strlen($search)) {
                $this->searchRecordList($query, $search);
            }
        }

        if ($controls) {
            $this->applyRecordFilters($query, $contextKey);
        }

        $query->paginateWith($this->request->query);

        $keyName = $this->getRecordKeyName();
        $searchBar = $filterBar = $selectBar = null;

        // Search
        if (static::CAN_SEARCH && $controls) {
            $searchBar = $this->apex->component('SearchBar');
        }


        // List
        if ($controls) {
            $fields = $this->mergeFilterListFields($fields, $contextKey);
        }

        $list = $this->apex->component(ucfirst($keyName).'List', $fields)
            ->setCollection($query)
            ->setSlot('scaffold', $this);


        // Relevance
        if ($query->hasSearch()) {
            $list->addCustomField('relevance', function ($list) {
                $list->addFieldAtIndex(0, 'relevance', function ($record) {
                    return $this->html->progressBar($record['relevance'] * 100);
                });
            });
        }

        // Filters
        if ($controls) {
            $filterBar = $this->renderRecordFilters($contextKey);
        }

        // Select Bar
        if (static::CAN_SELECT && $controls) {
            $selectBar = $this->apex->component('SelectBar');

            $list->addCustomField('select', function ($list) {
                $list->addFieldAtIndex(0, 'select', '✓', function ($record) {
                    return $this->html->checkbox('select[]', null, null, $record['id'])
                        ->addClass('selection');
                });
            });
        }


        if ($controls) {
            yield Html::{'?div.scaffold.controls'}([
                Html::{'?div.left'}([$searchBar, $selectBar]),
                Html::{'?div.right'}($filterBar)
            ]);
        }

        yield $list;
    }



    // Helpers
    public function getDirectoryKeyName(): string
    {
        return $this->getRecordKeyName();
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => [
            'context' => $this->context,
            '*record' => $this->record
        ];
    }
}
