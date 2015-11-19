<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold;

use df;
use df\core;
use df\arch;
use df\aura;
use df\opal;

// Exceptions
interface IException {}
class LogicException extends \LogicException implements IException {}
class RuntimeException extends \RuntimeException implements IException {}
class ActionNotFoundException extends RuntimeException {}


// Interfaces
interface IScaffold extends core\IRegistryObject, arch\IOptionalDirectoryAccessLock {
    public function loadAction();
    public function onActionDispatch(arch\action\IAction $action);
    public function loadComponent($name, array $args=null);
    public function loadFormDelegate($name, arch\action\IFormStateController $state, $id);
    public function loadMenu($name, $id);

    public function getPropagatingQueryVars();

    public function getDirectoryTitle();
    public function getDirectoryIcon();
    public function getDirectoryKeyName();
}

interface IRecordLoaderScaffold extends IScaffold {
    public function getRecordAdapter();
}

interface IRecordDataProviderScaffold extends IRecordLoaderScaffold {
    public function newRecord(array $values=null);
    public function getRecord();
    public function getRecordKeyName();
    public function getRecordUrlKey();
    public function getRecordId($record=null);
    public function getRecordIdField();
    public function getRecordName($record=null);
    public function getRecordNameField();
    public function getRecordDescription($record=null);
    public function getRecordUrl($record=null);
    public function getRecordIcon($record=null);
    public function getRecordOperativeLinks($record, $mode);

    public function decorateRecordLink($link, $component);

    public function canAddRecord();
    public function canEditRecord($record=null);
    public function canDeleteRecord($record=null);

    public function getRecordDeleteFlags();
    public function deleteRecord(opal\record\IRecord $record, array $flags=[]);
}

interface IRecordListProviderScaffold extends IRecordLoaderScaffold {
    public function queryRecordList($mode, array $fields=null);
    public function extendRecordList(opal\query\ISelectQuery $query, $mode);
    public function applyRecordListSearch(opal\query\ISelectQuery $query, $search);

    public function renderRecordList(opal\query\ISelectQuery $query=null, array $fields=null, $callback=null, $queryMode=null);
}

interface ISectionProviderScaffold extends IScaffold {
    public function loadSectionAction();
    public function buildSection($name, $builder, $linkBuilder=null);
    public function getSectionItemCounts();
}