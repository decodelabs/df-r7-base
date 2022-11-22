<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\scaffold;

use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Tagged as Html;
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

    public const ADAPTER = null;
    public const KEY_NAME = null;
    public const ITEM_NAME = null;
    public const URL_KEY = null;

    public const ID_FIELD = 'id';
    public const NAME_FIELD = null;

    public const DEFAULT_SECTION = 'details';

    public const CAN_ADD = true;
    public const CAN_PREVIEW = false;
    public const CAN_EDIT = true;
    public const CAN_DELETE = true;
    public const CONFIRM_DELETE = true;
    public const DELETE_PERMANENT = true;
    public const IS_PARENT = false;
    public const IS_SHARED = false;

    public const CAN_SEARCH = true;
    public const CAN_SELECT = false;

    public const SECTIONS = null;

    public const DETAILS_FIELDS = null;
    public const LIST_FIELDS = null;
    public const SEARCH_FIELDS = null;


    // Nodes
    public function indexHtmlNode()
    {
        return $this->buildRecordListNode();
    }


    // Components
    public function renderRecordList(?callable $filter = null, array $fields = null, ?string $contextKey = null, bool $controls = true)
    {
        $queryMode = $this->request->getNode();
        $query = $this->queryRecordList($queryMode);

        if ($filter) {
            $filter($query);
        }

        if (static::CAN_SEARCH && $controls) {
            $search = $this->request->getQueryTerm('search');

            if (strlen((string)$search)) {
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

        $list = $this->apex->component(ucfirst($keyName) . 'List', $fields)
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
                $list->addFieldAtIndex(0, 'select', '✓', function ($record) { // @ignore-non-ascii
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
