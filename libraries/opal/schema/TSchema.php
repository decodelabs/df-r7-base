<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\schema;

use DecodeLabs\Exceptional;

use df\opal;

trait TSchema
{
    protected $_isAudited = true;

    //protected $_options = [];
    protected $_optionChanges = [];

    public function __construct($name)
    {
        $this->setName($name);
    }


    // Audit
    public function isAudited(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isAudited = $flag;
            return $this;
        }

        return $this->_isAudited;
    }


    // Name
    public function setName($name)
    {
        return $this->setOption('name', $name);
    }

    public function getName(): string
    {
        return $this->getOption('name');
    }

    // Comment
    public function setComment($comment)
    {
        if (!strlen((string)$comment)) {
            $comment = null;
        }

        return $this->setOption('comment', $comment);
    }

    public function getComment()
    {
        return $this->getOption('comment');
    }


    // Options
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }

        return $this;
    }

    public function setOption($key, $value)
    {
        if (array_key_exists($key, $this->_options)) {
            if ($this->_isAudited && $value != $this->_options[$key]) {
                $this->_optionChanges[$key] = $value;
            }
        }

        $this->_options[$key] = $value;
        return $this;
    }

    public function getOption($key)
    {
        if (isset($this->_options[$key])) {
            return $this->_options[$key];
        }
    }

    public function getOptions()
    {
        return $this->_options;
    }

    public function getOptionChanges()
    {
        return $this->_optionChanges;
    }

    protected function _hasOptionChanges()
    {
        return !empty($this->_optionChanges);
    }

    protected function _acceptOptionChanges()
    {
        $this->_optionChanges = [];
    }


    // Ext. Serialize

    public function toJson(): string
    {
        return (string)json_encode($this->toStorageArray());
    }

    protected function _setGenericStorageArray(array $data)
    {
        $this->_options = (array)$data['opt'];
    }

    protected function _getGenericStorageArray()
    {
        return ['opt' => $this->_options];
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'properties' => $this->_getOptionDumpList();

        if (method_exists($this, '_getFieldDumpList')) {
            yield 'property:*fields' => $this->_getFieldDumpList();
        }

        if (method_exists($this, '_getIndexDumpList')) {
            $indexes = $this->_getIndexDumpList();

            if (!empty($indexes)) {
                yield 'property:*indexes' => $indexes;
            }
        }

        if (method_exists($this, '_getForeignKeyDumpList')) {
            $foreignKeys = $this->_getForeignKeyDumpList();

            if (!empty($foreignKeys)) {
                yield 'property:*foreignKeys' => $foreignKeys;
            }
        }

        if (method_exists($this, '_getTriggerDumpList')) {
            $triggers = $this->_getTriggerDumpList();

            if (!empty($triggers)) {
                yield 'property:*triggers' => $triggers;
            }
        }
    }

    protected function _getOptionDumpList()
    {
        $output = [
            'isAudited' => $this->_isAudited
        ];

        foreach ($this->_options as $key => $value) {
            if (array_key_exists($key, $this->_optionChanges)) {
                $key .= ' *';
            }

            $output[$key] = $value;
        }

        return $output;
    }
}





/*****************
 * Field provider
 */
trait TSchema_FieldProvider
{
    protected $_fields = [];
    protected $_addFields = [];
    protected $_renameFields = [];
    protected $_removeFields = [];
    protected $_replacedFields = [];

    public function getField($name)
    {
        if (isset($this->_fields[$name])) {
            return $this->_fields[$name];
        }

        return null;
    }

    public function createField($name, $type, ...$args)
    {
        return $this->_createField($name, $type, $args);
    }

    public function addField($name, $type, ...$args)
    {
        return $this->addPreparedField($this->_createField($name, $type, $args));
    }

    public function addPreparedField(opal\schema\IField $field)
    {
        $name = $field->getName();

        if (isset($this->_fields[$name])) {
            throw Exceptional::Runtime(
                'Field ' . $name . ' has already been defined, use replaceField() instead'
            );
        }

        if ($this->_isAudited) {
            $this->_addFields[$name] = $field;
        }

        $this->_fields[$name] = $field;
        return $field;
    }



    public function addFieldAfter($key, $name, $type, ...$args)
    {
        return $this->addPreparedFieldAfter(
            $key,
            $this->_createField($name, $type, $args)
        );
    }

    public function addPreparedFieldAfter($key, opal\schema\IField $field)
    {
        if ($key !== null && !isset($this->_fields[$key])) {
            throw Exceptional::InvalidArgument(
                'Field ' . $key . ' could not be found to place ' . $field->getName() . ' after'
            );
        }

        if (isset($this->_fields[$name = $field->getName()])) {
            throw Exceptional::Runtime(
                'Field ' . $name . ' has already been defined, use replaceField() instead'
            );
        }

        if ($this->_isAudited) {
            $this->_addFields[$field->getName()] = $field;
        }

        $this->_remapFields($key, $field);
        return $field;
    }

    public function replaceField($name, $type, ...$args)
    {
        return $this->replacePreparedField($this->_createField($name, $type, $args));
    }

    public function replacePreparedField(opal\schema\IField $field)
    {
        $name = $field->getName();

        if (!isset($this->_fields[$name])) {
            throw Exceptional::InvalidArgument(
                'Field ' . $name . ' could not be found'
            );
        }

        $this->_replacedFields[$name] = $oldField = $this->_fields[$name];

        $name = $field->getName();
        $this->_fields[$name] = $field;

        if (isset($this->_addFields[$name])) {
            $this->_addFields[$name] = $field;
        }

        if (isset($this->_removeFields[$name])) {
            $this->_removeFields[$name] = $field;
        }



        if ($this instanceof IIndexProvider) {
            foreach ($this->getIndexes() as $index) {
                $index->_updateFieldReference($oldField, $field);
            }
        }

        if ($this instanceof IForeignKeyProvider) {
            foreach ($this->getForeignKeys() as $key) {
                $key->replaceField($oldField, $field, false);
            }
        }

        return $field;
    }

    public function removeField($name)
    {
        if (isset($this->_fields[$name])) {
            $origName = $this->getOriginalFieldNameFor($name);
            $this->_fields[$name]->_setName($origName);

            if ($this instanceof IIndexProvider) {
                foreach ($this->getIndexes() as $index) {
                    if ($index->hasField($this->_fields[$name])) {
                        $this->removeIndex($index->getName());
                    }
                }
            }

            if ($this->_isAudited) {
                if (!isset($this->_addFields[$name])) {
                    $this->_removeFields[$origName] = $this->_fields[$name];
                }

                unset($this->_renameFields[$name]);
            }

            unset($this->_fields[$name]);
        }

        if (isset($this->_addFields[$name])) {
            unset($this->_addFields[$name]);
        }

        return $this;
    }

    public function renameField($oldName, $newName)
    {
        if ($oldName == $newName) {
            return $this;
        }

        if (isset($this->_fields[$newName])) {
            throw Exceptional::Runtime(
                'Cannot rename field ' . $oldName . ' to ' . $newName . ', a field with that name has already been defined'
            );
        }

        if (isset($this->_fields[$oldName])) {
            $this->_replacedFields[$oldName] = clone $this->_fields[$oldName];
            $this->_fields[$oldName]->_setName($newName);

            if ($this->_isAudited) {
                $this->_renameFields[$newName] = $this->getOriginalFieldNameFor($oldName);
            }

            $this->_remapFields();
            $this->_remapAddFields();
        }

        return $this;
    }

    public function getReplacedField($name)
    {
        if (isset($this->_replacedFields[$name])) {
            return $this->_replacedFields[$name];
        }
    }

    public function getFields()
    {
        return $this->_fields;
    }

    public function getFieldsToAdd()
    {
        return $this->_addFields;
    }

    public function getFieldsToUpdate()
    {
        $output = [];

        foreach ($this->_fields as $name => $field) {
            if ($field->hasChanged() && !isset($this->_addFields[$name])) {
                $output[$this->getOriginalFieldNameFor($name)] = $field;
            }
        }

        return $output;
    }

    public function getFieldRenameMap()
    {
        return $this->_renameFields;
    }

    public function getFieldsToRemove()
    {
        return $this->_removeFields;
    }

    public function hasField($name)
    {
        return isset($this->_fields[$name]) || isset($this->_addFields[$name]);
    }

    protected function _remapFields($insertKey = null, opal\schema\IField $insertField = null)
    {
        $fields = $this->_fields;
        $this->_fields = [];

        if ($insertField !== null && $insertKey === null) {
            $this->_fields[$insertField->getName()] = $insertField;
        }

        foreach ($fields as $field) {
            $name = $field->getName();
            $this->_fields[$name] = $field;

            if ($insertField !== null && $name == $insertKey) {
                $this->_fields[$insertField->getName()] = $insertField;
            }
        }
    }

    protected function _remapAddFields()
    {
        $addFields = $this->_addFields;
        $this->_addFields = [];

        foreach ($addFields as $field) {
            $this->_addFields[$field->getName()] = $field;
        }
    }

    public function getOriginalFieldNameFor($name)
    {
        if (isset($this->_renameFields[$name])) {
            return $this->_renameFields[$name];
        }

        return $name;
    }

    abstract public function _createField($name, $type, array $args);
    abstract public function _createFieldFromStorageArray(array $data);


    protected function _hasFieldChanges()
    {
        return !empty($this->_addFields)
            || !empty($this->_renameFields)
            || !empty($this->_removeFields);
    }

    protected function _acceptFieldChanges()
    {
        foreach ($this->_fields as $field) {
            $field->acceptChanges();
        }

        foreach ($this->_removeFields as $field) {
            $field->acceptChanges();
        }

        $this->_addFields = [];
        $this->_renameFields = [];
        $this->_removeFields = [];
    }


    // Ext. serialize
    protected function _setFieldStorageArray(array $data)
    {
        foreach ($data['fld'] as $field) {
            $this->addPreparedField(
                $this->_createFieldFromStorageArray($field)
            );
        }
    }

    protected function _getFieldStorageArray()
    {
        $output = ['fld' => []];

        foreach ($this->_fields as $field) {
            $output['fld'][] = $field->toStorageArray();
        }

        return $output;
    }

    // Dump
    protected function _getFieldDumpList()
    {
        $fields = [];

        foreach ($this->_fields as $name => $field) {
            $displayName = $name;

            if (isset($this->_addFields[$name])) {
                $displayName = '+ ' . $displayName;
            } elseif ($field->hasChanged()) {
                if (isset($this->_renameFields[$name])) {
                    $displayName = $this->_renameFields[$name] . ' -> ' . $name;
                }

                $displayName .= ' *';
            }

            $fields[$displayName] = $field;
        }

        foreach ($this->_removeFields as $name => $field) {
            $fields['- ' . $name] = $field;
        }

        return $fields;
    }
}








/***************************
 * Indexes
 */
trait TSchema_IndexProvider
{
    protected $_indexes = [];
    protected $_addIndexes = [];
    protected $_renameIndexes = [];
    protected $_removeIndexes = [];
    protected $_primaryIndex;
    protected $_lastPrimaryIndex;
    protected $_hasPrimaryIndexChanged = false;


    public function getIndex($name)
    {
        if (isset($this->_indexes[$name])) {
            return $this->_indexes[$name];
        }

        return null;
    }

    public function createIndex($name, $fields = null)
    {
        return $this->_createIndex($name, $this->_normalizeIndexFieldInput($fields, $name));
    }

    public function createUniqueIndex($name, $fields = null)
    {
        return $this->_createIndex($name, $this->_normalizeIndexFieldInput($fields, $name))->isUnique(true);
    }

    public function addIndex($name, $fields = null)
    {
        return $this->addPreparedIndex(
            $this->_createIndex(
                $name,
                $this->_normalizeIndexFieldInput($fields, $name)
            )
        );
    }

    public function addUniqueIndex($name, $fields = null)
    {
        return $this->addIndex($name, $fields)->isUnique(true);
    }

    public function addPrimaryIndex($name, $fields = null)
    {
        $index = $this->addUniqueIndex($name, $fields);
        $this->setPrimaryIndex($index);
        return $index;
    }

    public function addPreparedIndex(opal\schema\IIndex $index)
    {
        if (!$this->_validateIndex($index)) {
            throw Exceptional::Runtime(
                'Index ' . $index->getName() . ' is not valid for fields: ' . implode(', ', array_keys($index->getFields()))
            );
        }

        if (isset($this->_indexes[$index->getName()])) {
            throw Exceptional::Runtime(
                'Index ' . $index->getName() . ' has already been defined'
            );
        }

        if ($this->_isAudited) {
            $this->_addIndexes[$index->getName()] = $index;
        }

        $this->_indexes[$index->getName()] = $index;
        return $index;
    }

    protected function _validateIndex(opal\schema\IIndex $index)
    {
        return true;
    }

    public function replaceIndex($name, $fields = null)
    {
        return $this->replacePreparedIndex(
            $this->_createIndex(
                $name,
                $this->_normalizeIndexFieldInput($fields, $name)
            )
        );
    }

    public function replacePreparedIndex(opal\schema\IIndex $index)
    {
        $name = $index->getName();

        if (!isset($this->_indexes[$name])) {
            throw Exceptional::InvalidArgument(
                'Index ' . $name . ' could not be found'
            );
        }

        if ($this->_indexes[$name] === $this->_primaryIndex) {
            $this->_primaryIndex = $index;
        }


        $this->_indexes[$name] = $index;

        if (isset($this->_addIndexes[$name])) {
            $this->_addIndexes[$name] = $index;
        }

        if (isset($this->_removeIndexes[$name])) {
            $this->_removeIndexes[$name] = $index;
        }

        return $index;
    }

    public function removeIndex($name)
    {
        if (isset($this->_indexes[$name])) {
            $origName = $this->getOriginalIndexNameFor($name);
            $this->_indexes[$name]->_setName($origName);

            if ($this->_isAudited) {
                if (!isset($this->_addIndexes[$name])) {
                    $this->_removeIndexes[$origName] = $this->_indexes[$name];
                }

                unset($this->_renameIndexes[$name]);
            }

            unset($this->_indexes[$name]);
        }

        if (isset($this->_addIndexes[$name])) {
            unset($this->_addIndexes[$name]);
        }

        return $this;
    }

    public function renameIndex($oldName, $newName)
    {
        if ($oldName == $newName) {
            return $this;
        }

        if (isset($this->_indexes[$newName])) {
            throw Exceptional::Runtime(
                'Cannot rename index ' . $oldName . ' to ' . $newName . ', an index with that name has already been defined'
            );
        }

        if (isset($this->_indexes[$oldName])) {
            $this->_indexes[$oldName]->_setName($newName);

            if ($this->_isAudited) {
                $this->_renameIndexes[$newName] = $this->getOriginalIndexNameFor($oldName);
            }

            $this->_remapIndexes();
            $this->_remapAddIndexes();
        }

        return $this;
    }

    public function setPrimaryIndex($index)
    {
        $name = $index;

        if ($index !== null) {
            if ($index instanceof opal\schema\IIndex) {
                if ($this->getIndex($index->getName()) !== $index) {
                    throw Exceptional::InvalidArgument(
                        'Primary index is not from this schema'
                    );
                }
            } elseif (!$index = $this->getIndex($index)) {
                throw Exceptional::InvalidArgument(
                    'Index ' . $name . ' could not be found in this schema'
                );
            }

            if (!$index->isUnique()) {
                throw Exceptional::InvalidArgument(
                    'Primary indexes must be unique, ' . $index->getName() . ' is not'
                );
            }
        }

        if ($index !== $this->_primaryIndex) {
            if (!$this->_hasPrimaryIndexChanged) {
                $this->_lastPrimaryIndex = $this->_primaryIndex;
            }

            $this->_hasPrimaryIndexChanged = true;
        }

        $this->_primaryIndex = $index;
        return $this;
    }

    public function getPrimaryIndex()
    {
        return $this->_primaryIndex;
    }

    public function getPrimaryFields()
    {
        if ($this->_primaryIndex) {
            return $this->_primaryIndex->getFields();
        }
    }

    public function getLastPrimaryIndex()
    {
        return $this->_lastPrimaryIndex;
    }

    public function hasPrimaryIndexChanged()
    {
        if ($this->_hasPrimaryIndexChanged) {
            return true;
        }

        if (!$this->_primaryIndex) {
            return false;
        }

        foreach ($this->_primaryIndex->getFields() as $field) {
            if ($field->hasChanged()) {
                return true;
            }
        }

        return false;
    }

    public function getIndexes()
    {
        return $this->_indexes;
    }

    public function getIndexesFor(opal\schema\IField $field)
    {
        $output = [];

        foreach ($this->_indexes as $index) {
            if ($index->firstFieldIs($field)) {
                $output[] = $index;
            }
        }

        return $output;
    }

    public function getAllIndexesFor(opal\schema\IField $field)
    {
        $output = [];

        foreach ($this->_indexes as $index) {
            if ($index->hasField($field)) {
                $output[] = $index;
            }
        }

        return $output;
    }

    public function getIndexesToAdd()
    {
        return $this->_addIndexes;
    }

    public function getIndexesToUpdate()
    {
        $output = [];

        foreach ($this->_indexes as $name => $index) {
            if ($index->hasChanged() && !isset($this->_addIndexes[$name])) {
                $output[$this->getOriginalIndexNameFor($name)] = $index;
            }
        }

        return $output;
    }

    public function getIndexRenameMap()
    {
        return $this->_renameIndexes;
    }

    public function getIndexesToRemove()
    {
        return $this->_removeIndexes;
    }

    public function hasIndex($name)
    {
        return isset($this->_indexes[$name]) || isset($this->_addIndexes[$name]);
    }

    abstract public function _createIndex($name, $fields = null);
    abstract public function _createIndexFromStorageArray(array $data);

    public function getOriginalIndexNameFor($name)
    {
        if (isset($this->_renameIndexes[$name])) {
            return $this->_renameIndexes[$name];
        }

        return $name;
    }

    protected function _normalizeIndexFieldInput($fields, $name = null)
    {
        if ($fields === false) {
            return null;
        }

        if ($fields === null && $name !== null) {
            $fields = [$name];
        }

        if (is_string($fields) || $fields instanceof opal\schema\IField) {
            $fields = [$fields];
        }

        if (is_array($fields)) {
            foreach ($fields as $i => $field) {
                $size = null;
                $isDescending = false;

                if (is_array($field)) {
                    $t = array_shift($field);
                    $size = array_shift($field);
                    $isDescending = array_shift($field);
                    $field = $t;
                    unset($t);
                }

                if (is_string($field)
                && preg_match('/^([a-zA-Z0-9_-]+)[ ]?(\(([\d]+)\))?[ ]?(ASC|DESC)?/i', $field, $matches)) {
                    $field = $matches[1];

                    if (isset($matches[3])) {
                        $size = $matches[3];
                    }

                    if (isset($matches[4])) {
                        $direction = $matches[4];
                    }

                    $field = $this->getField($field);
                }

                if ($field instanceof opal\schema\IField) {
                    $fields[$i] = new IndexFieldReference($field, $size, $isDescending);
                }
            }
        } else {
            $fields = null;
        }

        return $fields;
    }

    protected function _remapIndexes()
    {
        $indexes = $this->_indexes;
        $this->_indexes = [];

        foreach ($indexes as $index) {
            $this->_indexes[$index->getName()] = $index;
        }
    }

    protected function _remapAddIndexes()
    {
        $addIndexes = $this->_addIndexes;
        $this->_addIndexes = [];

        foreach ($addIndexes as $index) {
            $this->_addFields[$index->getName()] = $index;
        }
    }

    protected function _hasIndexChanges()
    {
        return !empty($this->_addIndexes)
            || !empty($this->_renameIndexes)
            || !empty($this->_removeIndexes);
    }

    protected function _acceptIndexChanges()
    {
        foreach ($this->_indexes as $index) {
            $index->acceptChanges();
        }

        foreach ($this->_removeIndexes as $index) {
            $index->acceptChanges();
        }

        $this->_addIndexes = [];
        $this->_renameIndexes = [];
        $this->_removeIndexes = [];

        return $this;
    }


    // Ext. serialize
    protected function _setIndexStorageArray(array $data)
    {
        foreach ($data['idx'] as $index) {
            $this->addPreparedIndex(
                $this->_createIndexFromStorageArray($index)
            );
        }

        if (isset($data['pdx'])) {
            $this->_primaryIndex = $this->getIndex($data['pdx']);
        }
    }

    protected function _getIndexStorageArray()
    {
        $output = ['idx' => []];

        foreach ($this->_indexes as $index) {
            $output['idx'][] = $index->toStorageArray();
        }

        if ($this->_primaryIndex) {
            $output['pdx'] = $this->_primaryIndex->getName();
        }

        return $output;
    }


    // Dump
    protected function _getIndexDumpList()
    {
        $indexes = [];

        foreach ($this->_indexes as $name => $index) {
            $displayName = $name;

            if ($index === $this->_primaryIndex) {
                $displayName = '@ ' . $displayName;
            }

            if (isset($this->_addIndexes[$name])) {
                $displayName = '+ ' . $displayName;
            } elseif ($index->hasChanged()) {
                if (isset($this->_renameIndexes[$name])) {
                    $displayName = $this->_renameIndexes[$name] . ' -> ' . $name;
                }

                $displayName .= ' *';
            }

            $indexes[$displayName] = $index;
        }

        foreach ($this->_removeIndexes as $name => $index) {
            $indexes['- ' . $name] = $index;
        }

        return $indexes;
    }
}




/*********************
 * Indexed fields
 */
trait TSchema_IndexedFieldProvider
{
    public function addPrimaryField($name, $type, ...$args)
    {
        $field = $this->addField($name, $type, ...$args);
        $this->addPrimaryIndex($field->getName(), $field);
        return $field;
    }

    public function addPrimaryFieldAfter($key, $name, $type, ...$args)
    {
        $field = $this->addFieldAfter($key, $name, $type, ...$args);
        $this->addPrimaryIndex($field->getName(), $field);
        return $field;
    }

    public function addIndexedField($name, $type, ...$args)
    {
        $field = $this->addField($name, $type, ...$args);
        $this->addIndex($field->getName(), $field);
        return $field;
    }

    public function addIndexedFieldAfter($key, $name, $type, ...$args)
    {
        $field = $this->addFieldAfter($key, $name, $type, ...$args);
        $this->addIndex($field->getName(), $field);
        return $field;
    }

    public function addUniqueField($name, $type, ...$args)
    {
        $field = $this->addField($name, $type, ...$args);
        $this->addUniqueIndex($field->getName(), $field);
        return $field;
    }

    public function addUniqueFieldAfter($key, $name, $type, ...$args)
    {
        $field = $this->addFieldAfter($key, $name, $type, ...$args);
        $this->addUniqueIndex($field->getName(), $field);
        return $field;
    }
}





/*********************
 * Foreign keys
 */
trait TSchema_ForeignKeyProvider
{
    protected $_foreignKeys = [];
    protected $_addForeignKeys = [];
    protected $_renameForeignKeys = [];
    protected $_removeForeignKeys = [];


    public function getForeignKey($name)
    {
        if (isset($this->_foreignKeys[$name])) {
            return $this->_foreignKeys[$name];
        }

        return null;
    }

    public function addForeignKey($name, $targetSchema)
    {
        return $this->addPreparedForeignKey(
            $this->_createForeignKey($name, $targetSchema)
        );
    }

    public function addPreparedForeignKey(IForeignKey $key)
    {
        $name = $key->getName();

        if (isset($this->_foreignKeys[$name])) {
            throw Exceptional::Runtime(
                'Foreign key ' . $name . ' has already been defined'
            );
        }

        if ($this->_isAudited) {
            $this->_addForeignKeys[$name] = $key;
        }

        $this->_foreignKeys[$name] = $key;
        return $key;
    }

    public function replaceForeignKey($name, $targetSchema)
    {
        return $this->replacePreparedForeignKey(
            $this->_createForeignKey($name, $targetSchema)
        );
    }

    public function replacePreparedForeignKey(IForeignKey $key)
    {
        $name = $key->getName();

        if (!isset($this->_foreignKeys[$name])) {
            throw Exceptional::InvalidArgument(
                'Foreign key ' . $name . ' could not be found'
            );
        }

        $this->_foreignKeys[$name] = $key;

        if (isset($this->_addForeignKeys[$name])) {
            $this->_addForeignKeys[$name] = $key;
        }

        if (isset($this->_removeForeignKeys[$name])) {
            $this->_removeForeignKeys[$name] = $key;
        }

        return $key;
    }

    public function removeForeignKey($name)
    {
        if (isset($this->_foreignKeys[$name])) {
            $origName = $this->getOriginalForeignKeyNameFor($name);
            $this->_foreignKeys[$name]->_setName($origName);

            if ($this->_isAudited) {
                if (!isset($this->_addForeignKeys[$name])) {
                    $this->_removeForeignKeys[$origName] = $this->_foreignKeys[$name];
                }

                unset($this->_renameForeignKeys[$name]);
            }

            unset($this->_foreignKeys[$name]);
        }

        if (isset($this->_addForeignKeys[$name])) {
            unset($this->_addForeignKeys[$name]);
        }

        return $this;
    }

    public function renameForeignKey($oldName, $newName)
    {
        if ($oldName == $newName) {
            return $this;
        }

        if (isset($this->_foreignKeys[$newName])) {
            throw Exceptional::Runtime(
                'Cannot rename foreign key ' . $oldName . ' to ' . $newName . ', a foreign key with that name has already been defined'
            );
        }

        if (isset($this->_foreignKeys[$oldName])) {
            $this->_foreignKeys[$oldName]->_setName($newName);

            if ($this->_isAudited) {
                $this->_renameForeignKeys[$newName] = $this->getOriginalForeignKeyNameFor($oldName);
            }

            $this->_remapForeignKeys();
            $this->_remapAddForeignKeys();
        }

        return $this;
    }

    public function getForeignKeys()
    {
        return $this->_foreignKeys;
    }

    public function getForeignKeysToAdd()
    {
        return $this->_addForeignKeys;
    }

    public function getForeignKeysToUpdate()
    {
        $output = [];

        foreach ($this->_foreignKeys as $name => $key) {
            if ($key->hasChanged() && !isset($this->_addForeignKeys[$name])) {
                $output[$this->getOriginalForeignKeyNameFor($name)] = $key;
            }
        }

        return $output;
    }

    public function getForeignKeyRenameMap()
    {
        return $this->_renameForeignKeys;
    }

    public function getForeignKeysToRemove()
    {
        return $this->_removeForeignKeys;
    }

    public function hasForeignKey($name)
    {
        return isset($this->_foreignKeys[$name]) || isset($this->_addForeignKeys[$name]);
    }

    abstract public function _createForeignKey($name, $targetSchema);
    abstract public function _createForeignKeyFromStorageArray(array $data);

    public function getOriginalForeignKeyNameFor($name)
    {
        if (isset($this->_renameForeignKeys[$name])) {
            return $this->_renameForeignKeys[$name];
        }

        return $name;
    }

    protected function _remapForeignKeys()
    {
        $keys = $this->_foreignKeys;
        $this->_foreignKeys = [];

        foreach ($keys as $key) {
            $this->_foreignKeys[$key->getName()] = $key;
        }
    }

    protected function _remapAddForeignKeys()
    {
        $addKeys = $this->_addForeignKeys;
        $this->_addForeignKeys = [];

        foreach ($addKeys as $key) {
            $this->_addForeignKeys[$key->getName()] = $key;
        }
    }


    protected function _hasForeignKeyChanges()
    {
        return !empty($this->_addForeignKeys)
            || !empty($this->_renameForeignKeys)
            || !empty($this->_removeForeignKeys);
    }

    protected function _acceptForeignKeyChanges()
    {
        foreach ($this->_foreignKeys as $key) {
            $key->acceptChanges();
        }

        foreach ($this->_removeForeignKeys as $key) {
            $key->acceptChanges();
        }

        $this->_addForeignKeys = [];
        $this->_renameForeignKeys = [];
        $this->_removeForeignKeys = [];

        return $this;
    }


    // Ext. serialize
    protected function _setForeignKeyStorageArray(array $data)
    {
        foreach ($data['fky'] as $key) {
            $this->addPreparedForeignKey(
                $this->_createForeignKeyFromStorageArray($key)
            );
        }
    }

    protected function _getForeignKeyStorageArray()
    {
        $output = ['fky' => []];

        foreach ($this->_foreignKeys as $key) {
            $output['fky'][] = $key->toStorageArray();
        }

        return $output;
    }

    // Dump
    protected function _getForeignKeyDumpList()
    {
        $keys = [];

        foreach ($this->_foreignKeys as $name => $key) {
            $displayName = $name;

            if (isset($this->_addForeignKeys[$name])) {
                $displayName = '+ ' . $displayName;
            } elseif ($key->hasChanged()) {
                if (isset($this->_renameForeignKeys[$name])) {
                    $displayName = $this->_renameForeignKeys[$name] . ' -> ' . $name;
                }

                $displayName .= ' *';
            }

            $keys[$displayName] = $key;
        }

        foreach ($this->_removeForeignKeys as $name => $key) {
            $keys['- ' . $name] = $key;
        }

        return $keys;
    }
}







/************************
 * Triggers
 */
trait TSchema_TriggerProvider
{
    protected $_triggers = [];
    protected $_addTriggers = [];
    protected $_renameTriggers = [];
    protected $_removeTriggers = [];


    public function getTrigger($name)
    {
        if (isset($this->_triggers[$name])) {
            return $this->_triggers[$name];
        }

        return null;
    }

    public function addTrigger($name, $event, $timing, $statement)
    {
        return $this->addPreparedTrigger(
            $this->_createTrigger($name, $event, $timing, $statement)
        );
    }

    public function addPreparedTrigger(ITrigger $trigger)
    {
        $name = $trigger->getName();

        if (isset($this->_triggers[$name])) {
            throw Exceptional::{'df/opal/rdbms/TriggerConflict'}(
                'Trigger ' . $name . ' has already been defined'
            );
        }

        if ($this->_isAudited) {
            $this->_addTriggers[$name] = $trigger;
        }

        $this->_triggers[$name] = $trigger;
        return $trigger;
    }

    public function populateTrigger(ITrigger $trigger)
    {
        $this->_triggers[$trigger->getName()] = $trigger;
        return $this;
    }

    public function replaceTrigger($name, $event, $timing, $statement)
    {
        return $this->replacePreparedTrigger(
            $this->_createTrigger($name, $event, $timing, $statement)
        );
    }

    public function replacePreparedTrigger(ITrigger $trigger)
    {
        $name = $trigger->getName();

        if (!isset($this->_triggers[$name])) {
            throw Exceptional::UnexpectedValue(
                'Trigger ' . $name . ' could not be found'
            );
        }

        $this->_triggers[$name] = $trigger;

        if (isset($this->_addTriggers[$name])) {
            $this->_addTriggers[$name] = $trigger;
        }

        if (isset($this->_removeTriggers[$name])) {
            $this->_removeTriggers[$name] = $trigger;
        }

        return $trigger;
    }

    public function removeTrigger($name)
    {
        if (isset($this->_triggers[$name])) {
            $origName = $this->getOriginalTriggerNameFor($name);
            $this->_triggers[$name]->_setName($origName);

            if ($this->_isAudited) {
                if (!isset($this->_addTriggers[$name])) {
                    $this->_removeTriggers[$origName] = $this->_triggers[$name];
                }

                unset($this->_renameTriggers[$name]);
            }

            unset($this->_triggers[$name]);
        }

        if (isset($this->_addTriggers[$name])) {
            unset($this->_addTriggers[$name]);
        }

        return $this;
    }

    public function renameTrigger($oldName, $newName)
    {
        if ($oldName == $newName) {
            return $this;
        }

        if (isset($this->_triggers[$newName])) {
            throw Exceptional::{'df/opal/rdbms/TriggerConflict'}(
                'Cannot rename trigger ' . $oldName . ' to ' . $newName . ', a field with that name has already been defined'
            );
        }

        if (isset($this->_triggers[$oldName])) {
            $this->_triggers[$oldName]->_setName($newName);

            if ($this->_isAudited) {
                $this->_renameTriggers[$newName] = $this->getOriginalTriggerNameFor($oldName);
            }

            $this->_remapTriggers();
            $this->_remapAddTriggers();
        }

        return $this;
    }

    public function getTriggers()
    {
        return $this->_triggers;
    }

    public function getTriggersToAdd()
    {
        return $this->_addTriggers;
    }

    public function getTriggersToUpdate()
    {
        $output = [];

        foreach ($this->_triggers as $name => $trigger) {
            if ($trigger->hasChanged() && !isset($this->_addTriggers[$name])) {
                $output[$this->getOriginalTriggerNameFor($name)] = $trigger;
            }
        }

        return $output;
    }

    public function getTriggerRenameMap()
    {
        return $this->_renameTriggers;
    }

    public function getTriggersToRemove()
    {
        return $this->_removeTriggers;
    }

    public function hasTrigger($name)
    {
        return isset($this->_triggers[$name]) || isset($this->_addTriggers[$name]);
    }

    public function getOriginalTriggerNameFor($name)
    {
        if (isset($this->_renameTriggers[$name])) {
            return $this->_renameTriggers[$name];
        }

        return $name;
    }

    protected function _remapTriggers()
    {
        $triggers = $this->_triggers;
        $this->_triggers = [];

        foreach ($triggers as $trigger) {
            $this->_triggers[$trigger->getName()] = $trigger;
        }
    }

    protected function _remapAddTriggers()
    {
        $addTriggers = $this->_addTriggers;
        $this->_addTriggers = [];

        foreach ($addTriggers as $trigger) {
            $this->_addTriggers[$trigger->getName()] = $trigger;
        }
    }


    protected function _hasTriggerChanges()
    {
        return !empty($this->_addTriggers)
            || !empty($this->_renameTriggers)
            || !empty($this->_removeTriggers);
    }

    protected function _acceptTriggerChanges()
    {
        foreach ($this->_triggers as $trigger) {
            $trigger->acceptChanges();
        }

        foreach ($this->_removeTriggers as $trigger) {
            $trigger->acceptChanges();
        }

        $this->_addTriggers = [];
        $this->_renameTriggers = [];
        $this->_removeTriggers = [];

        return $this;
    }

    abstract public function _createTrigger($name, $event, $timing, $statement);
    abstract public function _createTriggerFromStorageArray(array $data);


    // Ext. serialize
    protected function _setTriggerStorageArray(array $data)
    {
        foreach ($data['trg'] as $trigger) {
            $this->addPreparedTrigger(
                $this->_createTriggerFromStorageArray($trigger)
            );
        }
    }

    protected function _getTriggerStorageArray()
    {
        $output = ['trg' => []];

        foreach ($this->_triggers as $trigger) {
            $output['trg'][] = $trigger->toStorageArray();
        }

        return $output;
    }


    // Dump
    protected function _getTriggerDumpList()
    {
        $triggers = [];

        foreach ($this->_triggers as $name => $trigger) {
            $displayName = $name;

            if (isset($this->_addTriggers[$name])) {
                $displayName = '+ ' . $displayName;
            } elseif ($trigger->hasChanged()) {
                if (isset($this->_renameTriggers[$name])) {
                    $displayName = $this->_renameTriggers[$name] . ' -> ' . $name;
                }

                $displayName .= ' *';
            }

            $triggers[$displayName] = $trigger;
        }

        foreach ($this->_removeTriggers as $name => $trigger) {
            $triggers['- ' . $name] = $trigger;
        }

        return $triggers;
    }
}
