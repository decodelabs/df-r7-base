<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\component;

use df;
use df\core;
use df\arch;
use df\aura;

class RecordLink extends arch\component\template\RecordLink {
    
    protected $_scaffold;

    public function __construct(arch\scaffold\IRecordDataProviderScaffold $scaffold, array $args=null) {
        $this->_scaffold = $scaffold;
        $this->_icon = $scaffold->getDirectoryIcon();

        parent::__construct($scaffold->getContext(), $args);
    }

    protected function _getRecordId() {
        return $this->_scaffold->getRecordId($this->_record);
    }

    protected function _getRecordName() {
        $output = $this->_scaffold->getRecordName($this->_record);

        if($this->_scaffold->getRecordNameField() == 'slug') {
            $output = $this->html('samp', $output);
        }

        return $output;
    }

    protected function _getRecordUrl($id) {
        return $this->_scaffold->getRecordUrl($this->_record);
    }
}