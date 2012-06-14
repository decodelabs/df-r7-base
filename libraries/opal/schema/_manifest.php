<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\schema;

use df;
use df\core;
use df\opal;

interface IFieldSize {
    const TINY = 'tiny';
    const SMALL = 'small';
    const MEDIUM = 'medium';
    const LARGE = 'large';
    const HUGE = 'huge';
}


interface IConflictClause {
    const ROLLBACK = 1;
    const ABORT = 2;
    const FAIL = 3;
    const IGNORE = 4;
    const REPLACE = 5;
}


interface ITriggerEvent {
    const INSERT = 1;
    const UPDATE = 2;
    const DELETE = 3;
}

interface ITriggerTiming {
    const BEFORE = 0;
    const AFTER = 1;
    const INSTEAD_OF = 2;
}


// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}
class LogicException extends \LogicException implements IException {}
class UnexpectedValueException extends \UnexpectedValueException implements IException {}
class InvalidArgumentException extends \InvalidArgumentException implements IException {}


// Interfaces
interface IChangeTracker {
    public function hasChanged();
    public function acceptChanges();
}



interface ISchema extends IChangeTracker {
    public function setName($name);
    public function getName();
    public function setComment($comment);
    public function getComment();
    public function isAudited($flag=null);
    
    public function setOptions(array $options);
    public function setOption($key, $value);
    public function getOption($key);
    public function getOptions();
    public function getOptionChanges();
    
    public static function fromJson(ISchemaContext $schemaContext, $json);
    public function toJson();
    public function toStorageArray();
}

interface ISchemaContext {}


interface IFieldProvider extends ISchema {
    public function getField($name);
    public function createField($name, $type);
    public function addField($name, $type);
    public function addPreparedField(IField $field);
    public function addFieldAfter($key, $name, $type);
    public function addPreparedFieldAfter($key, IField $field);
    public function replaceField($name, $type);
    public function replacePreparedField(IField $field);
    public function removeField($name);
    public function renameField($oldName, $newName);
    public function getFields();
    public function getFieldsToAdd();
    public function getFieldsToUpdate();
    public function getFieldRenameMap();
    public function getFieldsToRemove();
}


interface IIndexProvider extends ISchema {
    public function getIndex($name);
    public function createIndex($name, $fields=null);
    public function createUniqueIndex($name, $fields=null);
    public function addIndex($name, $fields=null);
    public function addUniqueIndex($name, $fields=null);
    public function addPrimaryIndex($name, $fields=null);
    public function addPreparedIndex(IIndex $index);
    public function replaceIndex($name, $fields=null);
    public function replacePreparedIndex(IIndex $index);
    public function removeIndex($name);
    public function renameIndex($oldName, $newName);
    public function setPrimaryIndex($index);
    public function getPrimaryIndex();
    public function getLastPrimaryIndex();
    public function hasPrimaryIndexChanged();
    public function getIndexes();
    public function getIndexesFor(IField $field);
    public function getAllIndexesFor(IField $field);
    public function getIndexesToAdd();
    public function getIndexesToUpdate();
    public function getIndexRenameMap();
    public function getIndexesToRemove();
}


interface IForeignKeyProvider extends ISchema {
    public function getForeignKey($name);
    public function addForeignKey($name, $targetSchema);
    public function addPreparedForeignKey(IForeignKey $key);
    public function replaceForeignKey($name, $targetSchema);
    public function replacePreparedForeignKey(IForeignKey $key);
    public function removeForeignKey($name);
    public function renameForeignKey($oldName, $newName);
    public function getForeignKeys();
    public function getForeignKeysToAdd();
    public function getForeignKeysToUpdate();
    public function getForeignKeyRenameMap();
    public function getForeignKeysToRemove();
}


interface ITriggerProvider extends ISchema {
    public function getTrigger($name);
    public function addTrigger($name, $event, $timing, $statement);
    public function addPreparedTrigger(ITrigger $trigger);
    public function populateTrigger(ITrigger $trigger);
    public function replaceTrigger($name, $event, $timing, $statement);
    public function replacePreparedTrigger(ITrigger $trigger);
    public function removeTrigger($name);
    public function renameTrigger($oldName, $newName);
    public function getTriggers();
    public function getTriggersToAdd();
    public function getTriggersToUpdate();
    public function getTriggerRenameMap();
    public function getTriggersToRemove();
}




interface IField extends IChangeTracker {
    public function getFieldType();
    public function _setName($name);
    public function getName();
    public function setComment($comment);
    public function getComment();
    public function isNullable($flag=null);
    public function setDefaultValue($default);
    public function getDefaultValue();
    public function toStorageArray();
}


interface ICharacterSetAwareField extends IField, core\string\ICharacterSetAware {}

interface IBinaryCollationField extends IField {
    public function hasBinaryCollation($flag=null);
}

interface ILengthRestrictedField extends IField {
    public function setLength($length);
    public function getLength();
}

interface IBitSizeRestrictedField extends IField {
    public function setBitSize($size);
    public function getBitSize();
}

interface IByteSizeRestrictedField extends IField {
    public function setByteSize($size);
    public function getByteSize();
}

interface ILargeByteSizeRestrictedField extends IField {
    public function setExponentSize($size);
    public function getExponentSize();
}

interface INumericField extends IField {
    public function isUnsigned($flag=null);
    public function shouldZerofill($flag=null);
}

interface IFloatingPointNumericField extends INumericField {
    public function setPrecision($precision);
    public function getPrecision();
    public function setScale($scale);
    public function getScale();
}


interface IAutoIncrementableField extends INumericField {
    public function shouldAutoIncrement($flag=null);
}

interface IAutoTimestampField extends IField {
    public function shouldTimestampOnUpdate($flag=null);
    public function shouldTimestampAsDefault($flag=null);
}

interface IOptionProviderField extends IField {
    public function setOptions(array $otions);
    public function getOptions();
}





interface IPrimitive extends IField {
    public function getType();
}

interface IMultiFieldPrimitive extends IPrimitive {
    public function getPrimitives();
}





interface IIndex extends IChangeTracker {
    public function _setName($name);
    public function getName();
    public function isUnique($flag=null);
    public function setComment($comment);
    public function getComment();
    
    public function setFields($fields);
    public function addField(IField $field, $size=null, $isDescending=false);
    public function addFieldReference(IIndexFieldReference $reference);
    public function replaceField(IField $oldField, IField $newField, $size=null, $isDescending=false);
    public function removeField(IField $field);
    public function _updateFieldReference(IField $oldField, IField $newField);
    public function firstFieldIs(IField $field);
    public function hasField(IField $field);
    public function getFieldReferences();
    public function getFields();
    public function isVoid();
}

interface IIndexFieldReference {
    public function _setField(IField $field);
    public function getField();
    public function setSize($size);
    public function getSize();
    public function isDescending($flag=null);
    public function toStorageArray();
}


interface IForeignKey extends IChangeTracker {
    public function _setName($name);
    public function getName();
    public function setTargetSchema($table);
    public function getTargetSchema();
    
    public function addReference(IField $field, $targetFieldName);
    public function removeReference(IField $field, $targetFieldName);
    public function replaceField(IField $oldField, IField $newField, $markChange=true);
    public function hasField(IField $field);
    public function getReferences();
    public function isVoid(); 
    
    public function setUpdateAction($action);
    public function getUpdateAction();
    public function setDeleteAction($action);
    public function getDeleteAction();
}


interface IForeignKeyFieldReference {
    public function _setField(IField $field);
    public function getField();
    public function _setTargetFieldName($targetField);
    public function getTargetFieldName();
    public function eq(IForeignKeyFieldReference $reference);
    public function toStorageArray();
}



interface ITrigger extends IChangeTracker {
    public function _setName($name);
    public function getName();
    public function setEvent($event);
    public function getEvent();
    public function getEventName();
    public function setTiming($timing);
    public function getTiming();
    public function getTimingName();
    public function setStatements($statements);
    public function addStatement($statement);
    public function getStatements();
    public function hasFieldReference($fields);
}