<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\navigation\menu;

use df;
use df\core;
use df\arch;


// Interfaces
interface IMenu extends arch\IContextAware, arch\navigation\IEntryListGenerator {
    public function getId();
    public function setSubId($id);
    public function getSubId();
    public function getDisplayName();
    public function getSource();
    public function getSourceId();
    
    public function initDelegates();
    public function addDelegate(IMenu $menu);
    public function getDelegates();
}


interface IConfig extends core\IConfig {
    public function createEntries(IMenu $menu, IEntryList $entryList);
    public function setDelegatesFor($id, array $delegates);
    public function setEntriesFor($id, array $entries);
    public function getSettingsFor($id);
}


interface ISource {
    public function getName();
    public function getDisplayName();
    public function loadMenu(core\uri\Url $id);
}

interface IListableSource extends ISource {
    public function loadAllMenus(array $whiteList=null);
}

interface ISourceAdapter {
    public function loadMenu(ISource $source, core\uri\Url $id);
}


interface IEntryList extends arch\navigation\IEntryList {
    public function registerMenu(IMenu $menu);
    public function hasMenu($id);
}

class Cache extends core\cache\Base {}
