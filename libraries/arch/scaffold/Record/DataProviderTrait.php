<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\scaffold\Record;

use df;
use df\core;
use df\arch;
use df\aura;
use df\axis;
use df\opal;
use df\mesh;
use df\flex;
use df\user;

use df\arch\scaffold\ISectionProviderScaffold as SectionProviderScaffold;

use df\opal\record\IRecord as Record;
use df\arch\IRequest as DirectoryRequest;

use df\core\collection\Util as CollectionUtil;
use df\opal\query\Exception as QueryException;

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

    protected $_record;


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

        if ($adapter instanceof axis\ISchemaBasedStorageUnit) {
            return $adapter->getRecordKeyName();
        } elseif ($adapter instanceof axis\IUnit) {
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

                if ($adapter instanceof axis\ISchemaBasedStorageUnit) {
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

        return strtolower(flex\Text::formatName($this->getRecordKeyName()));
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

            if ($adapter instanceof axis\IUnit) {
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
    public function newRecord(array $values=null)
    {
        return $this->data->newRecord($this->getRecordAdapter(), $values);
    }

    public function getRecord()
    {
        if ($this->_record) {
            return $this->_record;
        }

        $key = $this->context->request->query[$this->getRecordUrlKey()];
        $this->_record = $this->loadRecord($key);

        if (!$this->_record) {
            throw Exceptional::{
                'arch/scaffold/UnexpectedValue,arch/scaffold/NotFound'
            }('Unable to load scaffold record');
        }

        return $this->_record;
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
    public function getRecordId($record=null)
    {
        if (!$record) {
            $record = $this->getRecord();
        }

        if ($record instanceof opal\record\IPrimaryKeySetProvider) {
            return (string)$record->getPrimaryKeySet();
        }

        return $this->idRecord($record);
    }

    protected function idRecord($record)
    {
        $idKey = $this->getRecordIdField();
        return @$record[$idKey];
    }

    public function getRecordName($record=null)
    {
        if (!$record) {
            $record = $this->getRecord();
        }

        $key = $this->getRecordNameField();
        $output = $this->nameRecord($record);

        return $this->_normalizeFieldOutput($key, $output);
    }

    protected function nameRecord($record)
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
            } elseif ($record instanceof core\collection\IMappedCollection) {
                $available = $record->has($key);
            } else {
                $available = true;
            }

            $id = $this->getRecordId($record);

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

        return $output;
    }

    public function getRecordDescription($record=null)
    {
        if (!$record) {
            $record = $this->getRecord();
        }

        return $this->describeRecord($record);
    }

    protected function describeRecord($record)
    {
        return $this->getRecordName($record);
    }

    public function getRecordUrl($record=null)
    {
        if (!$record) {
            $record = $this->getRecord();
        }

        if ($this instanceof SectionProviderScaffold) {
            $default = $this->getDefaultSection();
        } else {
            $default = 'details';
        }

        return $this->getRecordNodeUri($record, $default);
    }

    public function getRecordIcon($record=null): ?string
    {
        if (!$record) {
            try {
                $record = $this->getRecord();
            } catch (\Throwable $e) {
                return $this->getDirectoryIcon();
            }
        }

        if (method_exists($this, 'iconifyRecord')) {
            return $this->iconifyRecord($record);
        } else {
            return $this->getDirectoryIcon();
        }
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
    public function getRecordNodeUri($record, string $node, array $query=null, $redirFrom=null, $redirTo=null, array $propagationFilter=[]): DirectoryRequest
    {
        return $this->getNodeUri($node, [
            $this->getRecordUrlKey() => $this->getRecordId($record)
        ], $redirFrom, $redirTo, $propagationFilter);
    }

    public function getRecordParentUri($record): DirectoryRequest
    {
        return $this->uri->directoryRequest($this->getParentSectionRequest());
    }
}
