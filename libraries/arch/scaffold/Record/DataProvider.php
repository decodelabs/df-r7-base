<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Record;

use df\opal;


use df\arch\IRequest as DirectoryRequest;
use df\arch\scaffold\IScaffold as Scaffold;
use df\opal\record\IRecord as Record;

interface DataProvider extends Scaffold
{
    // Key names
    public function getRecordKeyName(): string;
    public function getRecordIdField(): string;
    public function getRecordNameField(): string;
    public function getRecordNameFieldMaxLength(): int;
    public function getRecordUrlKey(): string;
    public function getRecordItemName(): string;

    // Adapter
    public function getRecordAdapter();

    // Record IO
    public function newRecord(array $values=null);
    public function getRecord();
    public function deleteRecord(Record $record, array $flags=[]);

    // Record field data
    public function getRecordId($record=null);
    public function getRecordName($record=null);
    public function getRecordDescription($record=null);
    public function getRecordUrl($record=null);
    public function getRecordIcon($record=null): ?string;

    // Record interaction
    public function canAddRecords(): bool;
    public function canEditRecords(): bool;
    public function canEditRecord($record): bool;
    public function canDeleteRecords(): bool;
    public function canDeleteRecord($record): bool;
    public function recordDeleteRequiresConfirmation(): bool;
    public function getRecordDeleteFlags(): array;

    // URL locations
    public function getRecordNodeUri($record, string $node, array $query=null, $redirFrom=null, $redirTo=null, array $propagationFilter=[]): DirectoryRequest;
    public function getRecordParentUri($record): DirectoryRequest;





    // List
    public function queryRecordList($mode, array $fields=null);
    public function extendRecordList(opal\query\ISelectQuery $query, $mode);
    public function applyRecordListSearch(opal\query\ISelectQuery $query, $search);

    public function renderRecordList($query=null, array $fields=null, $callback=null, $queryMode=null);
}
