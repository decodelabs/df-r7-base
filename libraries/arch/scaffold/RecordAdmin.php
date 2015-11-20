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

    const RECORD_ADAPTER = null;
    const RECORD_KEY_NAME = null;
    const RECORD_ITEM_NAME = null;
    const RECORD_URL_KEY = null;

    const RECORD_ID_FIELD = 'id';
    const RECORD_NAME_FIELD = null;

    const DEFAULT_RECORD_NODE = 'details';

    const CAN_ADD_RECORD = true;
    const CAN_EDIT_RECORD = true;
    const CAN_DELETE_RECORD = true;

    const CAN_SEARCH = true;

    protected function __construct(arch\IContext $context) {
        parent::__construct($context);

        if(empty($this->_recordDetailsFields)) {
            $this->_recordDetailsFields = $this->_recordListFields;

            foreach($this->_recordDetailsFields as $key => $value) {
                if($key === 'actions' || $value === 'actions') {
                    unset($this->_recordDetailsFields[$key]);
                }
            }
        }
    }

// Nodes
    public function indexHtmlNode() {
        return $this->buildListNode();
    }

    public function buildListNode(opal\query\ISelectQuery $query=null, array $fields=null, $callback=null, $queryMode=null) {
        if($queryMode === null) {
            $queryMode = $this->request->getNode();
        }

        $this->view = $this->apex->newWidgetView();

        $this->view->content->push(
            $this->apex->component('IndexHeaderBar'),
            $this->renderRecordList($query, $fields, $callback, $queryMode)
        );

        return $this->view;
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
            ->setCollection($query);

        if($callback) {
            core\lang\Callback::factory($callback)->invokeArgs([
                $list, $searchBar
            ]);
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