<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\node\form;

use df;
use df\core;
use df\arch;
use df\opal;

class SelectorDelegate extends arch\node\form\SelectorDelegate {

    protected $_scaffold;

    public function __construct(arch\scaffold\IScaffold $scaffold, arch\node\IFormState $state, arch\node\IFormEventDescriptor $event, $id) {
        $this->_scaffold = $scaffold;
        parent::__construct($scaffold->getContext(), $state, $event, $id);
    }

    protected function setDefaultValues() {
        $name = $this->_scaffold->getRecordKeyName();

        if(isset($this->request[$name]) && !isset($this->values->selected)) {
            $this->setSelected($this->request[$name]);
        } else {
            parent::setDefaultValues();
        }
    }

    protected function _getBaseQuery($fields=null) {
        return $this->_scaffold->queryRecordList('selector', $fields);
    }

    protected function _applyQuerySearch(opal\query\IQuery $query, $search) {
        return $this->_scaffold->applyRecordListSearch($query, $search);
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
        return $this->_scaffold->getRecordDescription($result);
    }
}