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

use df\arch\scaffold\Record\DataProvider as RecordDataProvider;
use df\arch\scaffold\Record\DataProviderTrait as RecordDataProviderTrait;
use df\arch\scaffold\Record\Decorator as RecordDecorator;
use df\arch\scaffold\Record\DecoratorTrait as RecordDecoratorTrait;

use DecodeLabs\Glitch\Dumpable;

abstract class RecordAdmin extends arch\scaffold\Base implements
    RecordDataProvider,
    RecordDecorator,

    ISectionProviderScaffold,
    Dumpable
{
    use RecordDataProviderTrait;
    use RecordDecoratorTrait;

    use TScaffold_SectionProvider;

    use TScaffold_IndexHeaderBarProvider;
    use TScaffold_RecordIndexHeaderBarProvider;

    const ADAPTER = null;
    const KEY_NAME = null;
    const ITEM_NAME = null;
    const URL_KEY = null;

    const ID_FIELD = 'id';
    const NAME_FIELD = null;

    const DEFAULT_SECTION = 'details';

    const CAN_ADD = true;
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

    public function buildListNode($query=null, array $fields=null, $callback=null, $queryMode=null)
    {
        if ($queryMode === null) {
            $queryMode = $this->request->getNode();
        }

        return $this->buildNode(
            $this->renderRecordList($query, $fields, $callback, $queryMode)
        );
    }

    public function renderDetailsSectionBody($record)
    {
        $keyName = $this->getRecordKeyName();
        return $this->apex->component(ucfirst($keyName).'Details')
            ->setRecord($record);
    }


    // Components
    public function renderRecordList($query=null, array $fields=null, $callback=null, ?string $queryMode=null)
    {
        if ($queryMode === null) {
            $queryMode = $this->request->getNode();
        }

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

        if ($callback) {
            core\lang\Callback::call($callback, $list, $searchBar);
        }

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
