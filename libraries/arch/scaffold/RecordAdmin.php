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

abstract class RecordAdmin extends arch\scaffold\Base implements
    IRecordLoaderScaffold,
    IRecordDataProviderScaffold,
    IRecordListProviderScaffold,
    ISectionProviderScaffold {

    use TScaffold_RecordLoader;
    use TScaffold_RecordDataProvider;
    use TScaffold_RecordListProvider;

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

    const CAN_SEARCH = true;


// Nodes
    public function indexHtmlNode() {
        return $this->buildListNode();
    }

    public function buildNode($content) {
        $this->view = $this->apex->newWidgetView();

        $this->view->content->push(
            $this->apex->component('IndexHeaderBar'),
            $content
        );

        return $this->view;
    }

    public function buildListNode(opal\query\ISelectQuery $query=null, array $fields=null, $callback=null, $queryMode=null) {
        if($queryMode === null) {
            $queryMode = $this->request->getNode();
        }

        return $this->buildNode(
            $this->renderRecordList($query, $fields, $callback, $queryMode)
        );
    }

    public function renderDetailsSectionBody($record) {
        $keyName = $this->getRecordKeyName();

        return $this->apex->component(ucfirst($keyName).'Details')
            ->setRecord($record);
    }


// Components
    public function renderRecordList(opal\query\ISelectQuery $query=null, array $fields=null, $callback=null, $queryMode=null) {
        if($queryMode === null) {
            $queryMode = $this->request->getNode();
        }

        if($query) {
            $this->prepareRecordList($query, $queryMode);
        } else {
            $query = $this->queryRecordList($queryMode);
        }

        if(static::CAN_SEARCH) {
            $search = $this->request->getQueryTerm('search');

            if(strlen($search)) {
                $this->searchRecordList($query, $search);
            }
        }

        $query->paginateWith($this->request->query);

        $keyName = $this->getRecordKeyName();

        if(static::CAN_SEARCH) {
            $searchBar = $this->apex->component('SearchBar');
        } else {
            $searchBar = null;
        }

        $list = $this->apex->component(ucfirst($keyName).'List', $fields)
            ->setCollection($query)
            ->setSlot('scaffold', $this);

        if($callback) {
            core\lang\Callback::call($callback, $list, $searchBar);
        }

        if($query->hasSearch()) {
            $list->addCustomField('relevance', function($list) {
                $list->addFieldAtIndex(0, 'relevance', function($record) {
                    return $this->html->progressBar($record['relevance'] * 100);
                });
            });
        }

        return [
            'searchBar' => $searchBar,
            'collectionList' => $list
        ];
    }

// Helpers
    public function getDirectoryKeyName() {
        return $this->getRecordKeyName();
    }
}