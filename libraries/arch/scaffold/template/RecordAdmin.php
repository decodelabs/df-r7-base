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
    const RECORD_URL_KEY = null;

    protected function __construct(arch\IContext $context) {
        parent::__construct($context);

        if(empty($this->_sections)) {
            $this->_sections[] = 'details';
        }
    }

// Actions
    public function indexHtmlAction() {
        $keyName = $this->getRecordKeyName();
        $adapter = $this->getRecordAdapter();

        $query = $this->getRecordListQuery('index')
            ->paginateWith($this->request->query);

        $container = $this->aura->getWidgetContainer();
        $this->view = $container->getView();

        $container->push(
            $this->import->component('IndexHeaderBar', $this->_context->location),
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

    public function buildDeleteDynamicAction($controller) {
        $this->_recordAction = 'delete';
        return new arch\scaffold\form\Delete($this, $controller);
    }

// Helpers
    protected function _getDirectoryKeyName() {
        return $this->getRecordKeyName();
    }
}