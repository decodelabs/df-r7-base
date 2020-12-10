<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Record;

use df\arch\IRequest as DirectoryRequest;
use df\arch\scaffold\IScaffold as Scaffold;
use df\opal\record\IRecord as Record;
use df\opal\query\ISelectQuery as SelectQuery;

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
    public function newRecord(array $values=null): Record;
    public function getRecordUrlId(): ?string;
    public function getRecord();
    public function getActiveRow(): array;
    public function deleteRecord(Record $record, array $flags=[]);

    // List IO
    public function queryRecordList(string $mode, array $fields=null): SelectQuery;
    public function extendRecordList(SelectQuery $query, string $mode): SelectQuery;
    public function applyRecordListSearch(SelectQuery $query, ?string $search): SelectQuery;

    // Record field data
    public function getRecordId(): string;
    public function identifyRecord($record): string;
    public function getRecordName();
    public function nameRecord($record);
    public function getRecordDescription();
    public function describeRecord($record);
    public function getRecordIcon(): ?string;
    public function iconifyRecord($record): ?string;

    // Record interaction
    public function canAddRecords(): bool;
    public function canPreviewRecords(): bool;
    public function canEditRecords(): bool;
    public function canEditRecord($record): bool;
    public function canDeleteRecords(): bool;
    public function canDeleteRecord($record): bool;
    public function recordDeleteRequiresConfirmation(): bool;
    public function getRecordDeleteFlags(): array;

    // URL locations
    public function getRecordUri($record, ?string $node=null, array $query=null, $redirFrom=null, $redirTo=null, array $propagationFilter=[]): DirectoryRequest;
    public function getRecordParentUri(array $record): DirectoryRequest;
    public function getRecordPreviewUri(array $record): ?DirectoryRequest;
}
