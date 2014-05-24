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

    const RECORD_KEY_NAME = null;
    const RECORD_ITEM_NAME = null;

    const RECORD_ID_KEY = 'id';
    const RECORD_NAME_KEY = 'name';
    const RECORD_FALLBACK_NAME_KEY = 'name';
    const RECORD_URL_KEY = null;

    const CAN_ADD_RECORD = true;
    const CAN_EDIT_RECORD = true;
    const CAN_DELETE_RECORD = true;

    const USE_LIST_ACTION = false;

    protected function __construct(arch\IContext $context) {
        parent::__construct($context);

        if(empty($this->_sections)) {
            $this->_sections[] = 'details';
        }

        if(empty($this->_recordDetailsFields)) {
            $this->_recordDetailsFields = $this->_recordListFields;

            foreach($this->_recordDetailsFields as $key => $value) {
                if($key == 'actions' || $value == 'actions') {
                    unset($this->_recordDetailsFields[$key]);
                }
            }
        }
    }

// Actions
    public function indexHtmlAction() {
        return $this->_defaultListAction();
    }

    public function listHtmlAction() {
        if(!static::USE_LIST_ACTION) {
            $this->throwError(404, 'List not active');
        }

        return $this->_defaultListAction();
    }

    private function _defaultListAction() {
        $keyName = $this->getRecordKeyName();
        $adapter = $this->getRecordAdapter();

        $query = $this->getRecordListQuery('index');
        $search = $this->request->getQueryTerm('search');

        if(strlen($search)) {
            $this->applyRecordQuerySearch($query, $search, 'index');
        }

        $query->paginateWith($this->request->query);

        $container = $this->aura->getWidgetContainer();
        $this->view = $container->getView();

        $container->push(
            $this->import->component('IndexHeaderBar', $this->_context->location),

            $this->html->form($this->_context->location)->setMethod('get')->push(
                $this->html->fieldSet($this->_('Search'))->push(
                    $this->html->searchTextbox('search', $search),
                    $this->html->submitButton(null, $this->_('Go'))
                        ->setIcon('search')
                        ->setDisposition('positive'),

                    $this->html->link(
                            $this->_context->location->path->toString(), 
                            $this->_('Reset')
                        )
                        ->setIcon('refresh')
                )
            ),

            $this->import->component(ucfirst($keyName).'List', $this->_context->location)
                ->setCollection($query)
        );

        return $this->view;
    }

    public function renderDetailsSectionBody($record) {
        $keyName = $this->getRecordKeyName();

        return $this->import->component(ucfirst($keyName).'Details', $this->_context->location)
            ->setRecord($record);
    }

// Helpers
    protected function _getDirectoryKeyName() {
        return $this->getRecordKeyName();
    }
}