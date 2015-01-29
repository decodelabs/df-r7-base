<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\delegate;

use df;
use df\core;
use df\arch;

class ListSelector extends arch\form\template\ListSelectorDelegate {
    
    protected $_scaffold;

    public function __construct(arch\scaffold\IScaffold $scaffold, arch\form\IStateController $state, $id) {
        $this->_scaffold = $scaffold;
        parent::__construct($scaffold->getContext(), $state, $id);
    }

    protected function _fetchResultList(array $ids) {
        return $this->_scaffold->getRecordListQuery('selector')
            ->where($this->_scaffold->getRecordIdField(), 'in', $ids)
            ->chain([$this, 'applyDependencies']);
    }

    protected function _getResultId($result) {
        return $this->_scaffold->getRecordId($result);
    }

    protected function _getResultDisplayName($result) {
        return $this->_scaffold->getRecordName($result);
    }

    protected function _getOptionsList() {
        $idKey = $this->_scaffold->getRecordIdField();
        $nameField = $this->_scaffold->getRecordNameField();

        return $this->_scaffold->getRecordListQuery('selector', [$idKey, $nameField])
            ->chain([$this, 'applyDependencies'])
            ->toList($idKey, $nameField);
    }
}