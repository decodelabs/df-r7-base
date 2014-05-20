<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\delegate;

use df;
use df\core;
use df\arch;

class SearchSelector extends arch\form\template\SearchSelectorDelegate {
    
    protected $_scaffold;

    public function __construct(arch\scaffold\IScaffold $scaffold, arch\form\IStateController $state, $id) {
        $this->_scaffold = $scaffold;
        parent::__construct($scaffold->getContext(), $state, $id);
    }

    protected function _fetchResultList(array $ids) {
        return $this->_scaffold->getRecordListQuery('selector')
            ->where($this->_scaffold->getRecordIdKey(), 'in', $ids)
            ->chain([$this, 'applyDependencies']);
    }

    protected function _getSearchResultIdList($search, array $selected) {
        $idKey = $this->_scaffold->getRecordIdKey();

        return $this->data->britvic->framework->select('id')
            ->where($this->_scaffold->getRecordNameKey(), 'matches', $search)
            ->where($idKey, '!in', $selected)
            ->chain([$this, 'applyDependencies'])
            ->toList($idKey);
    }

    protected function _renderCollectionList($result) {
        return $this->import->component(ucfirst($this->_scaffold->getRecordKeyName().'List'), $this->_context->location, [
                'actions' => false
            ])
            ->setCollection($result);
    }
}