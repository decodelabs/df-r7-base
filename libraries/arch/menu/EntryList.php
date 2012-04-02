<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\menu;

use df;
use df\core;
use df\arch;

class EntryList implements IEntryList {
    
    protected $_entries = array();
    protected $_menus = array();
    
    public static function fromArray(array $entries) {
        return (new self())->addEntries($entries);
    }
    
    public function addEntries($entries) {
        if(!is_array($entries)) {
            $entries = func_get_args();
        }
        
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
        
        $this->_entries[$entry->getId()] = $entry;
        return $this;
    }
    
    public function getEntry($id) {
        if(isset($this->_entries[$id])) {
            return $this->_entries[$id];
        }
        
        return null;
    }
    
    public function getEntries() {
        $output = array();
        $weights = array();
        
        foreach($this->_entries as $entry) {
            $output[] = $entry;
            $weights[] = $entry->getWeight();
        }
        
        array_multisort($weights, SORT_ASC, $output);
        return $output;
    }
    
    public function registerMenu(IMenu $menu) {
        $this->_menus[(string)$menu->getId()] = true;
        return $this;
    }
    
    public function hasMenu($id) {
        $id = (string)Base::normalizeId($id);
        return isset($this->_menus[$id]);
    }
    
    public function __call($method, $args) {
        if(substr($method, 0, 3) == 'new') {
            return arch\menu\entry\Base::factoryArgs(substr($method, 3), $args);
        }
        
        throw new \BadMethodCallException('Method '.$method.' does not exist');
    }
}
