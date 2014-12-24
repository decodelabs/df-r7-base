<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\template;

use df;
use df\core;
use df\arch;
use df\aura;
use df\opal;

abstract class RecordAdmin extends arch\scaffold\Base implements
    arch\scaffold\IRecordLoaderScaffold,
    arch\scaffold\IRecordDataProviderScaffold,
    arch\scaffold\IRecordListProviderScaffold,
    arch\scaffold\ISectionProviderScaffold {

    use arch\scaffold\TScaffold_RecordLoader;
    use arch\scaffold\TScaffold_RecordDataProvider;
    use arch\scaffold\TScaffold_RecordListProvider;

    use arch\scaffold\TScaffold_SectionProvider;

    use arch\scaffold\TScaffold_IndexHeaderBarProvider;
    use arch\scaffold\TScaffold_RecordIndexHeaderBarProvider;

    const RECORD_ADAPTER = null;
    const CLUSTER = false;
    const GLOBAL_CLUSTER = false;
    const CLUSTER_KEY = null;

    const RECORD_KEY_NAME = null;
    const RECORD_ITEM_NAME = null;

    const RECORD_ID_KEY = 'id';
    const RECORD_NAME_KEY = 'name';
    const RECORD_FALLBACK_NAME_KEY = 'name';
    const RECORD_URL_KEY = null;

    const CAN_ADD_RECORD = true;
    const CAN_EDIT_RECORD = true;
    const CAN_DELETE_RECORD = true;

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

// Actions
    public function indexHtmlAction() {
        return $this->buildListAction();
    }

    public function buildListAction(opal\query\ISelectQuery $query=null, array $fields=null, $callback=null, $queryMode=null) {
        if($queryMode === null) {
            $queryMode = $this->request->getAction();
        }

        $container = $this->aura->getWidgetContainer();
        $this->view = $container->getView();

        $container->push(
            $this->import->component('IndexHeaderBar'),
            $this->renderRecordList($query, $fields, $callback, $queryMode)
        );

        return $this->view;
    }

    public function renderDetailsSectionBody($record) {
        $keyName = $this->getRecordKeyName();

        return $this->import->component(ucfirst($keyName).'Details')
            ->setRecord($record);
    }


// Components
    public function renderIndexSelectorArea() {
        return $this->_renderClusterSelector();
    }

    public function renderDetailsSelectorArea() {
        return $this->_renderClusterSelector();
    }

    public function renderRecordList(opal\query\ISelectQuery $query=null, array $fields=null, $callback=null, $queryMode=null) {
        if($queryMode === null) {
            $queryMode = $this->request->getAction();
        }

        if($query) {
            $this->_prepareRecordListQuery($query, $queryMode);
        } else {
            $query = $this->getRecordListQuery($queryMode);
        }

        $search = $this->request->getQueryTerm('search');

        if(strlen($search)) {
            $this->applyRecordQuerySearch($query, $search, 'index');
        }

        $query->paginateWith($this->request->query);

        $keyName = $this->getRecordKeyName();

        $searchBar = $this->import->component('SearchBar');
        $list = $this->import->component(ucfirst($keyName).'List', $fields)
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
            $searchBar, $list
        ];
    }

// Helpers
    public function onActionDispatch(arch\IAction $action) {
        return $this->_handleClusterSelection();
    }

    protected function _getDirectoryKeyName() {
        return $this->getRecordKeyName();
    }
}