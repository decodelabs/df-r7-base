<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation\menu;

use df;
use df\core;
use df\arch;

class EntryList implements IEntryList {
    
    protected $_entries = array();
    protected $_menus = array();
    protected $_isSorted = false;
    
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
        if(!$entry instanceof arch\navigation\IEntry) {
            if(is_array($entry)) {
                $entry = arch\navigation\entry\Base::fromArray($entry);
            } else {
                throw new arch\navigation\RuntimeException(
                    'Invalid entry definition detected'
                );
            }
        }
        
        $this->_entries[$entry->getId()] = $entry;
        $this->_isSorted = false;

        return $this;
    }
    
    public function getEntry($id) {
        if(isset($this->_entries[$id])) {
            return $this->_entries[$id];
        }
        
        return null;
    }
    
    public function getEntries() {
        if(!$this->_isSorted) {
            usort($this->_entries, function($a, $b) {
                return $a->getWeight() > $b->getWeight();
            });

            $this->_isSorted = true;
        }

        return $this->_entries;
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
            return arch\navigation\entry\Base::factoryArgs(substr($method, 3), $args);
        }
        
        throw new \BadMethodCallException('Method '.$method.' does not exist');
    }

    public function toArray() {
        return $this->getEntries();
    }
}
