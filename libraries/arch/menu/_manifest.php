<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\menu;

use df;
use df\core;
use df\arch;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class RecursionException extends RuntimeException {}
class EntryTypeNotFoundException extends RuntimeException {}
class SourceNotFoundException extends RuntimeException {}



// Interfaces
interface IMenu extends arch\IContextAware {
    public function getId();
    public function getDisplayName();
    public function getSource();
    public function getSourceId();
    
// Delegates
    public function initDelegates();
    public function addDelegate(IMenu $menu);
    public function getDelegates();
    
// Entries
    public function generateEntries(IEntryList $entryList);
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
    public function loadAllMenus(array $whiteList=null);
}


interface IEntryList {
    public function addEntries($entries);
    public function addEntry($entry);
    public function getEntries();
    public function registerMenu(IMenu $menu);
    public function hasMenu($id);
}

interface IEntry extends core\IArrayProvider {
    public function getType();
    
    public function setId($id);
    public function getId();
    
    public function setWeight($weight);
    public function getWeight();
}



class Cache extends core\cache\Base {}
