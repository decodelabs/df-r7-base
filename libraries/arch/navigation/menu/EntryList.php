<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\navigation\menu;

use df\arch;

class EntryList implements IEntryList
{
    use arch\navigation\TEntryList;

    protected $_menus = [];

    public function registerMenu(IMenu $menu)
    {
        $this->_menus[(string)$menu->getId()] = true;
        return $this;
    }

    public function hasMenu($id)
    {
        $id = (string)Base::normalizeId($id);
        return isset($this->_menus[$id]);
    }
}
