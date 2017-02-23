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

interface IScaffold extends core\IRegistryObject, arch\IOptionalDirectoryAccessLock {
    public function loadNode();
    public function onNodeDispatch(arch\node\INode $node);
    public function loadComponent($name, array $args=null);
    public function loadFormDelegate($name, arch\node\IFormState $state, arch\node\IFormEventDescriptor $event, $id);
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

    public function getRecordBackLinkRequest();
}

interface IRecordListProviderScaffold extends IRecordLoaderScaffold {
    public function queryRecordList($mode, array $fields=null);
    public function extendRecordList(opal\query\ISelectQuery $query, $mode);
    public function applyRecordListSearch(opal\query\ISelectQuery $query, $search);

    public function renderRecordList(opal\query\ISelectQuery $query=null, array $fields=null, $callback=null, $queryMode=null);
}

interface ISectionProviderScaffold extends IScaffold {
    public function loadSectionNode();
    public function buildSection($name, $builder, $linkBuilder=null);
    public function getSectionItemCounts();
}