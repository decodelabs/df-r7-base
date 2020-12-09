<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Record;

use df\arch\scaffold\Section\Provider as SectionProvider;

use df\opal\record\IRecord as Record;
use df\arch\IRequest as DirectoryRequest;

use df\core\collection\Util as CollectionUtil;
use df\core\collection\IMappedCollection as MappedCollection;

use df\opal\query\Exception as QueryException;
use df\opal\query\ISelectQuery as SelectQuery;
use df\opal\record\IPrimaryKeySetProvider as PrimaryKeySetProvider;

use df\axis\IUnit as Unit;
use df\axis\ISchemaBasedStorageUnit as SchemaBasedStorageUnit;

use df\flex\Text;

use DecodeLabs\Tagged\Html;
use DecodeLabs\Exceptional;

trait DataProviderTrait
{
    //const ADAPTER = null;
    //const KEY_NAME = null;
    //const ITEM_NAME = null;

    //const ID_FIELD = 'id';
    //const NAME_FIELD = 'name';
    //const URL_KEY = null;

    //const CAN_ADD = true;
    //const CAN_EDIT = true;
    //const CAN_DELETE = true;
    //const CONFIRM_DELETE = true;

    //const SEARCH_FIELDS = [];

    protected $record;


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
        static $output;

        if (!isset($output)) {
            if (
                defined('static::NAME_FIELD') &&
                static::NAME_FIELD !== null
            ) {
                $output = static::NAME_FIELD;
            } else {
                $adapter = $this->getRecordAdapter();

                if ($adapter instanceof SchemaBasedStorageUnit) {
                    $output = $adapter->getRecordNameField();
                } else {
                    $output = 'name';
                }
            }
        }

        return $output;
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

        return strtolower(Text::formatName($this->getRecordKeyName()));
    }



    // Adapter
    public function getRecordAdapter()
    {
        static $output;

        if (isset($output)) {
            return $output;
        }

        if (
            defined('static::ADAPTER') &&
            static::ADAPTER !== null
        ) {
            $adapter = $this->data->fetchEntity(static::ADAPTER);

            if ($adapter instanceof Unit) {
                $output = $adapter;
                return $output;
            }
        } elseif ($output = $this->generateRecordAdapter()) {
            return $output;
        }

        throw Exceptional::Definition(
            'Unable to find a suitable adapter for record scaffold'
        );
    }

    protected function generateRecordAdapter()
    {
    }


    // Record IO
    public function newRecord(array $values=null): Record
    {
        return $this->data->newRecord($this->getRecordAdapter(), $values);
    }

    public function getRecord()
    {
        if ($this->record) {
            return $this->record;
        }

        $key = $this->context->request->query[$this->getRecordUrlKey()];
        $this->record = $this->loadRecord($key);

        if (!$this->record) {
            throw Exceptional::{
                'df/arch/scaffold/UnexpectedValue,df/arch/scaffold/NotFound'
            }('Unable to load scaffold record');
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

    public function deleteRecord(Record $record, array $flags=[])//: void
    {
        $record->delete();
    }




    // List IO
    public function queryRecordList(string $mode, array $fields=null): SelectQuery
    {
        $output = $this->getRecordAdapter()->select($fields);
        $this->prepareRecordList($output, $mode);

        return $output;
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
                $output = '#'.$output;
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
                $output = [$output, Html::{'samp'}('#'.$id)];
            }
        }

        return $this->_normalizeFieldOutput($key, $output);
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




    // URL locations
    public function getRecordUri($record, ?string $node=null, array $query=null, $redirFrom=null, $redirTo=null, array $propagationFilter=[]): DirectoryRequest
    {
        if ($node === null) {
            if ($this instanceof SectionProvider) {
                $node = $this->getDefaultSection();
            } else {
                $node = 'details';
            }
        }

        return $this->getNodeUri($node, [
            $this->getRecordUrlKey() => $this->identifyRecord($record)
        ], $redirFrom, $redirTo, $propagationFilter);
    }

    public function getRecordParentUri($record): DirectoryRequest
    {
        if (!empty($str = $this->getRecordParentUriString($record))) {
            return $this->uri->directoryRequest($str);
        }

        return $this->getNodeUri('index');
    }

    protected function getRecordParentUriString($record): string
    {
        return '';
    }
}
