<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation\menu;

use df;
use df\core;
use df\arch;

class Config extends core\Config implements IConfig {
    
    const ID = 'menus';
    const IS_DISTRIBUTED = true;
    const STORE_IN_MEMORY = false;
    
    public function getDefaultValues() {
        return [];
    }
    
    public function createEntries(IMenu $menu, IEntryList $entryList) {
        $id = (string)$menu->getId();
        
        if(!isset($this->_values[$id]) || !is_array($this->_values[$id])) {
            return $this;
        }
        
        $context = $menu->getContext();
        
        if(isset($this->_values[$id]['delegates'])) {
            foreach($this->_values[$id]['delegates'] as $delegate) {
                try {
                    $menu->addDelegate(Base::factory($context, $delegate));
                } catch(IException $e) {
                    continue;
                }
            }
        }
        
        if(isset($this->_values[$id]['entries'])) {
            foreach($this->_values[$id]['entries']  as $entry) {
                $entryList->addEntry(
                    arch\navigation\entry\Base::fromArray($entry)
                );
            }
        }
        
        return $this;
    }
    
    public function setDelegatesFor($id, array $delegates) {
        $id = (string)Base::normalizeId($id);
        $this->_values[$id]['delegates'] = $delegates;
        
        return $this;
    }
    
    public function setEntriesFor($id, array $entries) {
        $id = (string)Base::normalizeId($id);
        
        foreach($entries as $entry) {
            try {
                if(!$entry instanceof arch\navigation\IEntry) {
                    if(is_array($entry)) {
                        $entry = arch\navigation\entry\Base::fromArray($entry);
                    } else {
                        continue;
                    }
                }
                
                $this->_values[$id]['entries'][] = $entry->toArray();
            } catch(IException $e) {
                continue;
            }
        }
        
        return $this;
    }
    
    public function getSettingsFor($id) {
        $id = (string)Base::normalizeId($id);
        
        if(isset($this->_values[$id])) {
            $output = $this->_values[$id];
        } else {
            $output = ['delegates' => [], 'entries' => []];
        }
        
        if(!isset($output['delegates'])) {
            $output['delegates'] = array();
        }
        
        if(!isset($output['entries'])) {
            $output['entries'] = array();
        }
        
        return $output;
    }
}
