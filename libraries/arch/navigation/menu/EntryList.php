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
    
    use arch\navigation\TEntryList;
    
    protected $_menus = array();
    
    protected function _sortEntries() {
        usort($this->_entries, function($a, $b) {
            return $a->getWeight() > $b->getWeight();
        });
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
