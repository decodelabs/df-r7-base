<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\scaffold\Record;

use DecodeLabs\Dictum;

use DecodeLabs\Exceptional;
use DecodeLabs\Tagged as Html;

use df\arch\IRequest as DirectoryRequest;
use df\arch\scaffold\Section\Provider as SectionProvider;

use df\axis\ISchemaBasedStorageUnit as SchemaBasedStorageUnit;
use df\axis\IUnit as Unit;
use df\core\collection\IMappedCollection as MappedCollection;

use df\core\collection\Util as CollectionUtil;
use df\core\time\IDate as Date;

use df\opal\query\Exception as QueryException;

use df\opal\query\ISelectQuery as SelectQuery;
use df\opal\record\IPrimaryKeySetProvider as PrimaryKeySetProvider;
use df\opal\record\IRecord as Record;

trait DataProviderTrait
{
    //const ADAPTER = null;
    //const KEY_NAME = null;
    //const ITEM_NAME = null;

    //const ID_FIELD = 'id';
    //const NAME_FIELD = 'name';
    //const URL_KEY = null;

    //const CAN_ADD = true;
    //const CAN_PREVIEW = false;
    //const CAN_EDIT = true;
    //const CAN_DELETE = true;
    //const CONFIRM_DELETE = true;
    //const DELETE_PERMANENT = true;
    //const IS_PARENT = false;
    //const IS_SHARED = false;

    //const SEARCH_FIELDS = [];

    protected $record;
    protected $row;

    private $recordAdapter;
    private string $recordNameField;


    // Key names
    public function getRecordKeyName(): string
    {
        if (
            defined('static::KEY_NAME') &&
            static::KEY_NAME !== null
        ) {
            return static::KEY_NAME;
        }

        $adapter = $this->getRecordAdapter();

        if ($adapter instanceof SchemaBasedStorageUnit) {
            return $adapter->getRecordKeyName();
        } elseif ($adapter instanceof Unit) {
            return lcfirst($adapter->getUnitName());
        } else {
            return 'record';
        }
    }

    public function getRecordIdField(): string
    {
        if (
            defined('static::ID_FIELD') &&
            static::ID_FIELD !== null
        ) {
            return static::ID_FIELD;
        }

        return 'id';
    }

    public function getRecordNameField(): string
    {
        if (!isset($this->recordNameField)) {
            if (
                defined('static::NAME_FIELD') &&
                static::NAME_FIELD !== null
            ) {
                $this->recordNameField = static::NAME_FIELD;
            } else {
                $adapter = $this->getRecordAdapter();

                if ($adapter instanceof SchemaBasedStorageUnit) {
                    $this->recordNameField = $adapter->getRecordNameField();
                } else {
                    $this->recordNameField = 'name';
                }
            }
        }

        return $this->recordNameField;
    }

    public function getRecordNameFieldMaxLength(): int
    {
        if (defined('static::NAME_KEY_FIELD_MAX_LENGTH')) {
            return (int)static::NAME_KEY_FIELD_MAX_LENGTH;
        }

        return 40;
    }

    public function getRecordUrlKey(): string
    {
        if (
            defined('static::URL_KEY') &&
            static::URL_KEY !== null
        ) {
            return static::URL_KEY;
        }

        return $this->getRecordKeyName();
    }

    public function getRecordItemName(): string
    {
        if (
            defined('static::ITEM_NAME') &&
            static::ITEM_NAME !== null
        ) {
            return static::ITEM_NAME;
        }

        return strtolower(Dictum::name($this->getRecordKeyName()));
    }



    // Adapter
    public function getRecordAdapter()
    {
        if (isset($this->recordAdapter)) {
            return $this->recordAdapter;
        }

        if (
            defined('static::ADAPTER') &&
            static::ADAPTER !== null
        ) {
            $adapter = $this->data->fetchEntity(static::ADAPTER);

            if ($adapter instanceof Unit) {
                return $this->recordAdapter = $adapter;
            }
        } elseif ($adapter = $this->generateRecordAdapter()) {
            return $this->recordAdapter = $adapter;
        }

        throw Exceptional::Definition(
            'Unable to find a suitable adapter for record scaffold'
        );
    }

    protected function generateRecordAdapter()
    {
    }


    // Record IO
    public function newRecord(array $values = null): Record
    {
        return $this->data->newRecord($this->getRecordAdapter(), $values);
    }

    public function getRecordUrlId(): ?string
    {
        return $this->context->request->query[$this->getRecordUrlKey()];
    }

    public function getRecord()
    {
        if ($this->record) {
            return $this->record;
        }

        if (null === ($id = $this->getRecordUrlId())) {
            throw Exceptional::{
                'df/arch/scaffold/UnexpectedValue,df/arch/scaffold/NotFound'
            }([
                'message' => 'Record ID not provided in URL',
                'http' => 404
            ]);
        }

        $this->record = $this->loadRecord($id);

        if (!$this->record) {
            throw Exceptional::{
                'df/arch/scaffold/UnexpectedValue,df/arch/scaffold/NotFound'
            }([
                'message' => 'Unable to load scaffold record',
                'http' => 404
            ]);
        }

        return $this->record;
    }

    protected function loadRecord($key)
    {
        return $this->data->fetchForAction(
            $this->getRecordAdapter(),
            $key
        );
    }

    public function getActiveRow(): array
    {
        if ($this->row) {
            return $this->row;
        }

        if (null === ($id = $this->getRecordUrlId())) {
            throw Exceptional::{
                'df/arch/scaffold/UnexpectedValue,df/arch/scaffold/NotFound'
            }([
                'message' => 'Record ID not provided in URL',
                'http' => 404
            ]);
        }

        $this->row = $this->loadActiveRow($id);
        return $this->row;
    }

    protected function loadActiveRow(string $id): array
    {
        return $this->data->queryForAction(
            $this->queryRecordList('row'),
            function ($query) use ($id) {
                $query->where($this->getRecordIdField(), '=', $id);
            }
        );
    }

    public function deleteRecord(Record $record, array $flags = [])//: void
    {
        $record->delete();
    }




    // List IO
    public function queryRecordList(string $mode, array $fields = null): SelectQuery
    {
        if ($fields === null) {
            $fields = $this->getDefaultRecordQueryFields();
        }

        $output = $this->getRecordAdapter()->select($fields);
        $this->prepareRecordList($output, $mode);

        return $output;
    }

    protected function getDefaultRecordQueryFields(): ?array
    {
        return null;
    }

    public function extendRecordList(SelectQuery $query, string $mode): SelectQuery
    {
        $this->prepareRecordList($query, $mode);
        return $query;
    }

    protected function prepareRecordList($query, $mode)
    {
    }

    public function applyRecordListSearch(SelectQuery $query, ?string $search): SelectQuery
    {
        $this->searchRecordList($query, $search);
        return $query;
    }

    protected function searchRecordList($query, $search)
    {
        if (defined('static::SEARCH_FIELDS')
        && is_array(static::SEARCH_FIELDS)
        && !empty(static::SEARCH_FIELDS)) {
            $fields = static::SEARCH_FIELDS;
        } else {
            $fields = null;
        }

        $query->searchFor($search, $fields);
    }



    // Record relations
    public function countRecordRelations(Record $record, ...$fields): array
    {
        $fields = CollectionUtil::flatten($fields);
        $query = $this->getRecordAdapter()->select('@primary');

        foreach ($fields as $field) {
            try {
                $query->countRelation($field);
            } catch (QueryException $e) {
            }
        }

        $output = [];
        $data = $query->where('@primary', '=', $record->getPrimaryKeySet())
            ->toRow();

        foreach ($fields as $key => $field) {
            if (!isset($data[$field])) {
                continue;
            }

            if (!is_string($key)) {
                $key = $field;
            }

            $output[$key] = $data[$field];
        }

        return $output;
    }


    // Record field data
    public function getRecordId(): string
    {
        return $this->identifyRecord($this->getRecord());
    }

    public function identifyRecord($record): string
    {
        if ($record instanceof PrimaryKeySetProvider) {
            return (string)$record->getPrimaryKeySet();
        }

        $idKey = $this->getRecordIdField();
        return (string)@$record[$idKey];
    }

    public function getRecordName()
    {
        return $this->nameRecord($this->getRecord());
    }

    public function nameRecord($record)
    {
        $key = $this->getRecordNameField();
        $output = null;

        if (isset($record[$key])) {
            $output = $record[$key];

            if ($key == $this->getRecordIdField() && is_numeric($output)) {
                $output = '#' . $output;
            }
        } else {
            if (is_array($record)) {
                $available = array_key_exists($key, $record);
            } elseif ($record instanceof MappedCollection) {
                $available = $record->has($key);
            } else {
                $available = true;
            }

            $id = $this->identifyRecord($record);

            if ($available) {
                switch ($key) {
                    case 'title':
                        $output = Html::{'em'}($this->_('untitled %c%', ['%c%' => $this->getRecordItemName()]));
                        break;

                    case 'name':
                        $output = Html::{'em'}($this->_('unnamed %c%', ['%c%' => $this->getRecordItemName()]));
                        break;
                }
            } else {
                $output = Html::{'em'}($this->getRecordItemName());
            }

            if (is_numeric($id)) {
                $output = [$output, Html::{'samp'}('#' . $id)];
            }
        }

        return $this->normalizeFieldOutput($key, $output);
    }

    protected function normalizeFieldOutput(string $field, $value)
    {
        if ($value instanceof Date) {
            return Html::$time->locale($value, $value->hasTime() ? 'short' : 'medium', 'short');
        }

        return $value;
    }

    public function getRecordDescription()
    {
        return $this->describeRecord($this->getRecord());
    }

    public function describeRecord($record)
    {
        return $this->nameRecord($record);
    }



    public function getRecordIcon(): ?string
    {
        try {
            $record = $this->getRecord();
        } catch (\Throwable $e) {
            return $this->getDirectoryIcon();
        }

        return $this->iconifyRecord($record);
    }

    public function iconifyRecord($record): ?string
    {
        return $this->getDirectoryIcon();
    }




    // Record interaction
    public function canAddRecords(): bool
    {
        if (defined('static::CAN_ADD')) {
            return (bool)static::CAN_ADD;
        }

        return false;
    }

    public function canPreviewRecords(): bool
    {
        if (defined('static::CAN_PREVIEW')) {
            return (bool)static::CAN_PREVIEW;
        }

        return false;
    }

    public function canEditRecords(): bool
    {
        if (defined('static::CAN_EDIT')) {
            return (bool)static::CAN_EDIT;
        }

        return false;
    }

    public function canEditRecord($record): bool
    {
        if (
            !$this->canEditRecords() ||
            $record === null
        ) {
            return false;
        }

        return $this->isRecordEditable($record);
    }


    protected function isRecordEditable($record): bool
    {
        return true;
    }

    public function canDeleteRecords(): bool
    {
        if (defined('static::CAN_DELETE')) {
            return (bool)static::CAN_DELETE;
        }

        return false;
    }

    public function canDeleteRecord($record): bool
    {
        if (
            !$this->canDeleteRecords() ||
            $record === null
        ) {
            return false;
        }

        return $this->isRecordDeleteable($record);
    }

    protected function isRecordDeleteable($record): bool
    {
        return true;
    }

    public function areRecordDeletesPermanent(): bool
    {
        if (defined('static::DELETE_PERMANENT')) {
            return (bool)static::DELETE_PERMANENT;
        }

        return true;
    }

    public function recordDeleteRequiresConfirmation(): bool
    {
        if (defined('static::CONFIRM_DELETE')) {
            return (bool)static::CONFIRM_DELETE;
        }

        return true;
    }

    public function getRecordDeleteFlags(): array
    {
        return [];
    }


    public function areRecordsParents(): bool
    {
        if (defined('static::IS_PARENT')) {
            return (bool)static::IS_PARENT;
        }

        return false;
    }

    public function areRecordsShared(): bool
    {
        if (defined('static::IS_SHARED')) {
            return (bool)static::IS_SHARED;
        }

        return false;
    }




    // URL locations
    public function getRecordUri($record, ?string $node = null, array $query = null, $redirFrom = null, $redirTo = null, array $propagationFilter = []): DirectoryRequest
    {
        if ($node === null) {
            if ($this instanceof SectionProvider) {
                $node = $this->getDefaultSection();
            } else {
                $node = 'details';
            }
        }

        return $this->getNodeUri($node, array_merge($query ?? [], [
            $this->getRecordUrlKey() => $this->identifyRecord($record)
        ]), $redirFrom, $redirTo, $propagationFilter);
    }

    public function getRecordParentUri(array $record): DirectoryRequest
    {
        if (empty($str = $this->getRecordParentUriString($record))) {
            return $this->getNodeUri('index');
        }

        return $this->uri->directoryRequest($str);
    }

    protected function getRecordParentUriString(array $record): ?string
    {
        return null;
    }


    public function getRecordPreviewUri(array $record): ?DirectoryRequest
    {
        if (empty($str = $this->getRecordPreviewUriString($record))) {
            return null;
        }

        return $this->uri->directoryRequest($str);
    }

    protected function getRecordPreviewUriString(array $record): ?string
    {
        return null;
    }
}
