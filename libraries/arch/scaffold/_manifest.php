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
    public function loadAction(arch\IController $controller=null);
    public function onActionDispatch(arch\IAction $action);
    public function loadComponent($name, array $args=null);
    public function loadFormDelegate($name, arch\form\IStateController $state, $id);
    public function loadMenu($name, $id);

    public function getPropagatingQueryVars();

    public function getDirectoryTitle();
    public function getDirectoryIcon();
}

interface IRecordLoaderScaffold extends IScaffold {
    public function getRecordAdapter();
    public function isRecordAdapterClustered();
    public function getClusterKey();
    public function getClusterRecord();
    public function getClusterId();
}

interface IRecordDataProviderScaffold extends IRecordLoaderScaffold {
    public function getRecord();
    public function getRecordKeyName();
    public function getRecordId($record=null);
    public function getRecordIdField();
    public function getRecordName($record=null);
    public function getRecordNameField();
    public function getRecordDescription($record=null);
    public function getRecordUrl($record=null);
    public function getRecordIcon($record=null);
    public function getRecordOperativeLinks($record, $mode);

    public function canAddRecord();
    public function canEditRecord($record=null);
    public function canDeleteRecord($record=null);

    public function getRecordDeleteFlags();
    public function deleteRecord(opal\record\IRecord $record, array $flags=[]);
}

interface IRecordListProviderScaffold extends IRecordLoaderScaffold {
    public function getRecordListQuery($mode, array $fields=null);
    public function applyRecordQuerySearch(opal\query\ISelectQuery $query, $search, $mode);

    public function renderRecordList(opal\query\ISelectQuery $query=null, array $fields=null, $queryMode=null);
}

interface ISectionProviderScaffold extends IScaffold {
    public function loadSectionAction(arch\IController $controller=null);
    public function buildSection($name, $builder, $linkBuilder=null);
    public function getSectionItemCounts();
}


interface IAction extends arch\IAction {
    public function getCallback();
    public function getScaffold();
}
