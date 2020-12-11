<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold;

use df;
use df\core;
use df\arch;
use df\aura;
use df\opal;

use df\arch\scaffold\Index\Decorator as IndexDecorator;
use df\arch\scaffold\Index\DecoratorTrait as IndexDecoratorTrait;
use df\arch\scaffold\Record\DataProvider as RecordDataProvider;
use df\arch\scaffold\Record\DataProviderTrait as RecordDataProviderTrait;
use df\arch\scaffold\Record\Decorator as RecordDecorator;
use df\arch\scaffold\Record\DecoratorTrait as RecordDecoratorTrait;
use df\arch\scaffold\Section\Decorator as SectionDecorator;
use df\arch\scaffold\Section\DecoratorTrait as SectionDecoratorTrait;
use df\arch\scaffold\Section\Provider as SectionProvider;
use df\arch\scaffold\Section\ProviderTrait as SectionProviderTrait;

use DecodeLabs\Glitch\Dumpable;

abstract class RecordAdmin extends arch\scaffold\Base implements
    IndexDecorator,
    RecordDataProvider,
    RecordDecorator,
    SectionDecorator,
    SectionProvider,
    Dumpable
{
    use IndexDecoratorTrait;
    use RecordDataProviderTrait;
    use RecordDecoratorTrait;
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

    const CAN_SEARCH = true;
    const CAN_SELECT = false;

    const SECTIONS = null;

    const DETAILS_FIELDS = null;
    const LIST_FIELDS = null;
    const SEARCH_FIELDS = null;


    // Nodes
    public function indexHtmlNode()
    {
        return $this->buildListNode();
    }

    public function buildNode($content)
    {
        $this->view = $this->apex->newWidgetView();

        $this->view->content->push(
            $this->apex->component('IndexHeaderBar'),
            $content
        );

        return $this->view;
    }

    public function buildListNode($query=null, array $fields=null)
    {
        return $this->buildNode(
            $this->renderRecordList($query, $fields)
        );
    }

    public function renderDetailsSectionBody($record)
    {
        $keyName = $this->getRecordKeyName();
        return $this->apex->component(ucfirst($keyName).'Details')
            ->setRecord($record);
    }


    // Components
    public function renderRecordList($query=null, array $fields=null)
    {
        $queryMode = $this->request->getNode();

        if ($query instanceof opal\query\ISelectQuery) {
            $this->prepareRecordList($query, $queryMode);
        } else {
            $extender = is_callable($query) ? $query : null;
            $query = $this->queryRecordList($queryMode);

            if ($extender) {
                $extender($query);
            }
        }

        if (static::CAN_SEARCH) {
            $search = $this->request->getQueryTerm('search');

            if (strlen($search)) {
                $this->searchRecordList($query, $search);
            }
        }

        $query->paginateWith($this->request->query);

        $keyName = $this->getRecordKeyName();
        $searchBar = $selectBar = null;

        if (static::CAN_SEARCH) {
            $searchBar = $this->apex->component('SearchBar');
        }

        $list = $this->apex->component(ucfirst($keyName).'List', $fields)
            ->setCollection($query)
            ->setSlot('scaffold', $this);

        if ($query->hasSearch()) {
            $list->addCustomField('relevance', function ($list) {
                $list->addFieldAtIndex(0, 'relevance', function ($record) {
                    return $this->html->progressBar($record['relevance'] * 100);
                });
            });
        }

        if (static::CAN_SELECT) {
            $selectBar = $this->apex->component('SelectBar');

            $list->addCustomField('select', function ($list) {
                $list->addFieldAtIndex(0, 'select', '✓', function ($record) {
                    return $this->html->checkbox('select[]', null, null, $record['id'])
                        ->addClass('selection');
                });
            });
        }

        return [
            'searchBar' => $searchBar,
            'selectBar' => $selectBar,
            'collectionList' => $list
        ];
    }



    // Helpers
    public function getDirectoryKeyName()
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
