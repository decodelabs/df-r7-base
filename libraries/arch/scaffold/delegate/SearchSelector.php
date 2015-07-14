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
            ->where($this->_scaffold->getRecordIdField(), 'in', $ids)
            ->chain([$this, 'applyFilters']);
    }

    protected function _getSearchResultIdList($search, array $selected) {
        $idKey = $this->_scaffold->getRecordIdField();

        return $this->_scaffold->getRecordListQuery('selector', [$idKey])
            ->chain(function($query) use($search) {
                $this->_scaffold->applyRecordQuerySearch($query, $search, 'selector');
            })
            ->where($idKey, '!in', $selected)
            ->chain([$this, 'applyFilters'])
            ->toList($idKey);
    }

    protected function _renderCollectionList($result) {
        return $this->apex->component(ucfirst($this->_scaffold->getRecordKeyName().'List'), [
                'actions' => false
            ])
            ->setCollection($result);
    }

    protected function _getResultId($result) {
        return $this->_scaffold->getRecordId($result);
    }

    protected function _getResultDisplayName($result) {
        return $this->_scaffold->getRecordName($result);
    }
}