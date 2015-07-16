<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\delegate;

use df;
use df\core;
use df\arch;
use df\opal;

class Selector extends arch\form\template\SelectorDelegate {
    
    protected $_scaffold;

    public function __construct(arch\scaffold\IScaffold $scaffold, arch\form\IStateController $state, $id) {
        $this->_scaffold = $scaffold;
        parent::__construct($scaffold->getContext(), $state, $id);
    }

    protected function _getBaseQuery($fields=null) {
        return $this->_scaffold->getRecordListQuery('selector', $fields);
    }

    protected function _applyQuerySearch(opal\query\IQuery $query, $search) {
        return $this->_scaffold->applyRecordQuerySearch($query, $search, 'selector');
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