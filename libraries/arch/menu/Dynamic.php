<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\menu;

use df;
use df\core;
use df\arch;

class Dynamic extends Base {
    
    protected $_recordId;
    protected $_displayName;
    protected $_entries = array();
    
    public function setRecordId($id) {
        $this->_recordId = $id;
        return $this;
    }
    
    public function getRecordId() {
        return $this->_recordId;
    }
    
    public function setDisplayName($name) {
        $this->_displayName = $name;
        return $this;
    }
    
    public function getDisplayName() {
        if($this->_displayName !== null) {
            return $this->_displayName;
        } else {
            return parent::getDisplayName();
        }
    }
    
    public function setEntries(array $entries) {
        $this->_entries = array();
        return $this->addEntries($entries);
    }
    
    public function addEntries(array $entries) {
        foreach($entries as $entry) {
            $this->addEntry($entry);
        }
        
        return $this;
    }
    
    public function addEntry($entry) {
        if(!$entry instanceof IEntry) {
            if(is_array($entry)) {
                $entry = arch\menu\entry\Base::fromArray($entry);
            } else {
                throw new RuntimeException(
                    'Invalid entry definition detected'
                );
            }
        }
        
        $this->_entries[] = $entry;
        return $this;
    }
    
    public function getEntries() {
        return $this->_entries;
    }
    
    protected function _createEntries(IEntryList $entryList) {
        $entryList->addEntries($this->_entries);
    }
}
