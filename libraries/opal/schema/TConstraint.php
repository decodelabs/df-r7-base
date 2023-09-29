<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\schema;

use DecodeLabs\Exceptional;

use df\opal;

trait TConstraint_CharacterSetAware
{
    protected $_characterSet;

    public function setCharacterSet($charset)
    {
        $this->_hasChanged = true;
        $this->_characterSet = $charset;
        return $this;
    }

    public function getCharacterSet()
    {
        return $this->_characterSet;
    }

    // Ext. serialize
    protected function _getCharacterSetStorageArray()
    {
        return ['chr' => $this->_characterSet];
    }
}


trait TConstraint_CollationAware
{
    protected $_collation;

    public function setCollation($collation)
    {
        $this->_hasChanged = true;
        $this->_collation = $collation;
        return $this;
    }

    public function getCollation()
    {
        return $this->_collation;
    }

    // Ext. serialize
    protected function _getCollationStorageArray()
    {
        return ['col' => $this->_collation];
    }
}



trait TConstraint_Base
{
    protected $_name;
    protected $_hasChanged = false;

    public function _setName($name)
    {
        if ($name != $this->_name) {
            $this->_hasChanged = true;
        }

        $this->_name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function hasChanged()
    {
        return $this->_hasChanged;
    }

    public function markAsChanged()
    {
        $this->_hasChanged = true;
        return $this;
    }

    public function acceptChanges()
    {
        $this->_hasChanged = false;
        return $this;
    }
}



/***********************
 * Index
 */
trait TConstraint_Index
{
    use TConstraint_Base;

    protected $_isUnique = false;
    protected $_fieldReferences = [];
    protected $_comment;


    public function __construct(?opal\schema\ISchema $schema, $name, $fields = null)
    {
        $this->_setName($name);
        $this->setFields($fields);

        if ($schema) {
            $schema->getName();
        }
    }

    public function isUnique(bool $flag = null)
    {
        if ($flag !== null) {
            if ($flag != $this->_isUnique) {
                $this->_hasChanged = true;
            }

            $this->_isUnique = $flag;
            return $this;
        }

        return $this->_isUnique;
    }

    public function setComment($comment)
    {
        $this->_comment = $comment;
        return $this;
    }

    public function getComment()
    {
        return $this->_comment;
    }

    public function setFields($fields)
    {
        if ($fields === null) {
            return $this;
        }

        if (!is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($fields as $field) {
            if ($field instanceof IField) {
                $this->addField($field);
            } elseif ($field instanceof IIndexFieldReference) {
                $this->addFieldReference($field);
            } else {
                throw Exceptional::InvalidArgument(
                    'Invalid field (' . (@(string)$field) . ') passed to index ' . $this->getName()
                );
            }
        }

        return $this;
    }

    public function addField(IField $field, $size = null, $isDescending = false)
    {
        return $this->addFieldReference(new IndexFieldReference($field, $size, $isDescending));
    }

    public function addFieldReference(IIndexFieldReference $reference)
    {
        if (!$this->hasField($reference->getField())) {
            $this->_fieldReferences[] = $reference;
            $this->_hasChanged = true;
        }

        return $this;
    }

    public function replaceField(IField $oldField, IField $newField, $size = null, $isDescending = false)
    {
        foreach ($this->_fieldReferences as $i => $reference) {
            if ($reference->getField() === $oldField) {
                $this->_fieldReferences[$i] = new IndexFieldReference($newField, $size, $isDescending);
                $this->_hasChanged = true;
                break;
            }
        }

        return $this;
    }

    public function removeField(IField $field)
    {
        foreach ($this->_fieldReferences as $i => $reference) {
            if ($field === $reference->getField()) {
                unset($this->_fieldReferences[$i]);
                $this->_fieldReferences = array_values($this->_fieldReferences);
                $this->_hasChanged = true;
                break;
            }
        }

        return $this;
    }

    public function _updateFieldReference(IField $oldField, IField $newField)
    {
        foreach ($this->_fieldReferences as $i => $reference) {
            if ($oldField === $reference->getField()) {
                $reference->_setField($newField);
                break;
            }
        }

        return $this;
    }

    public function firstFieldIs(IField $field)
    {
        return isset($this->_fieldReferences[0]) && $this->_fieldReferences[0]->getField() === $field;
    }

    public function hasField(IField $field)
    {
        foreach ($this->_fieldReferences as $reference) {
            if ($field === $reference->getField()) {
                return true;
            }
        }

        return false;
    }

    public function getFieldReferences()
    {
        return $this->_fieldReferences;
    }

    public function getFields()
    {
        $output = [];

        foreach ($this->_fieldReferences as $ref) {
            $field = $ref->getField();
            $output[$field->getName()] = $field;
        }

        return $output;
    }

    public function isSingleMultiPrimitiveField()
    {
        if (count($this->_fieldReferences) != 1) {
            return false;
        }

        return $this->_fieldReferences[0]->isMultiField();
    }

    public function isVoid()
    {
        return empty($this->_fieldReferences);
    }


    public function toStorageArray()
    {
        return $this->_getGenericStorageArray();
    }

    protected function _setGenericStorageArray(opal\schema\ISchema $schema, array $data)
    {
        $this->_name = $data['nam'];

        if (isset($data['uni'])) {
            $this->_isUnique = $data['uni'];
        } else {
            $this->_isUnique = false;
        }

        foreach ($data['fld'] as $field) {
            $this->_fieldReferences[] = IndexFieldReference::fromStorageArray($schema, $field);
        }

        if (isset($data['com'])) {
            $this->_comment = $data['com'];
        }
    }

    protected function _getGenericStorageArray()
    {
        $output = [
            'nam' => $this->_name,
            'fld' => []
        ];

        if ($this->_isUnique) {
            $output['uni'] = true;
        }

        if ($this->_comment !== null) {
            $output['com'] = $this->_comment;
        }

        foreach ($this->_fieldReferences as $ref) {
            $output['fld'][] = $ref->toStorageArray();
        }

        return $output;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        $output = $this->_name;

        if ($this->_isUnique) {
            $output .= ' UNIQUE';
        }

        $fields = [];

        foreach ($this->_fieldReferences as $reference) {
            $fieldDef = $reference->getField()->getName();

            if (null !== ($size = $reference->getSize())) {
                $fieldDef .= '(' . $size . ')';
            }

            if ($reference->isDescending()) {
                $fieldDef .= ' DESC';
            } else {
                $fieldDef .= ' ASC';
            }

            $fields[] = $fieldDef;
        }

        $output .= ' (' . implode(',', $fields) . ')';

        if ($this->isVoid()) {
            $output .= ' **VOID**';
        }

        yield 'definition' => $output;
    }
}



/*****************************
 * Foreign Key
 */
trait TConstraint_ForeignKey
{
    use TConstraint_Base;

    protected $_targetSchema;
    protected $_fieldReferences = [];
    protected $_updateAction;
    protected $_deleteAction;

    public function __construct($name, $targetSchema)
    {
        $this->_setName($name);
        $this->setTargetSchema($targetSchema);
    }

    public function setTargetSchema($schema)
    {
        if ($schema instanceof ISchema) {
            $schema = $schema->getName();
        }

        $this->_targetSchema = $schema;
        return $this;
    }

    public function getTargetSchema()
    {
        return $this->_targetSchema;
    }

    public function addReference(IField $field, $targetFieldName)
    {
        $reference = new ForeignKeyFieldReference($field, $targetFieldName);

        foreach ($this->_fieldReferences as $compReference) {
            if ($compReference->eq($reference)) {
                throw Exceptional::Runtime(
                    'A field reference between ' . $reference->getField()->getName() . ' and ' .
                    $this->_targetSchema . '.' . $reference->getTargetFieldName() . ' has already been defined'
                );
            }
        }

        $this->_fieldReferences[] = $reference;
        $this->_hasChanged = true;

        return $this;
    }

    public function removeReference(IField $field, $targetFieldName)
    {
        foreach ($this->_fieldReferences as $i => $compReference) {
            if ($compReference->eq($targetFieldName)) {
                unset($this->_fieldReferences[$i]);
                $this->_fieldReferences = array_values($this->_fieldReferences);
                $this->_hasChanged = true;
                break;
            }
        }

        return $this;
    }

    public function replaceField(IField $oldField, IField $newField, $markChange = true)
    {
        foreach ($this->_fieldReferences as $i => $reference) {
            if ($reference->getField() === $oldField) {
                $reference->_setField($newField);

                if ($markChange) {
                    $this->_hasChanged = true;
                }

                break;
            }
        }

        return $this;
    }

    public function hasField(IField $field)
    {
        foreach ($this->_fieldReferences as $reference) {
            if ($field === $reference->getField()) {
                return true;
            }
        }

        return false;
    }

    public function getReferences()
    {
        return $this->_fieldReferences;
    }

    public function setUpdateAction($action)
    {
        $action = $this->_normalizeAction($action);

        if ($this->_updateAction != $action) {
            $this->_hasChanged = true;
        }

        $this->_updateAction = $action;
        return $this;
    }

    public function getUpdateAction()
    {
        return $this->_updateAction;
    }

    public function setDeleteAction($action)
    {
        $action = $this->_normalizeAction($action);

        if ($this->_deleteAction != $action) {
            $this->_hasChanged = true;
        }

        $this->_deleteAction = $action;
        return $this;
    }

    public function getDeleteAction()
    {
        return $this->_deleteAction;
    }

    protected function _normalizeAction($action)
    {
        return $action;
    }

    public function isVoid()
    {
        return empty($this->_fieldReferences);
    }


    // Ext. serialize
    public function toStorageArray()
    {
        return $this->_getGenericStorageArray();
    }

    protected function _getGenericStorageArray()
    {
        $output = [
            'nam' => $this->_name,
            'tsc' => $this->_targetSchema,
            'fld' => [],
            'upd' => $this->_updateAction,
            'del' => $this->_deleteAction
        ];

        foreach ($this->_fieldReferences as $ref) {
            $output['fld'][] = $ref->toStorageArray();
        }

        return $output;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        $output = $this->_name;
        $refs = [];

        foreach ($this->_fieldReferences as $reference) {
            $refs[] = $reference->getField()->getName() . ' TO ' . $this->_targetSchema . '.' . $reference->getTargetFieldName();
        }

        $output .= ' ' . implode(', ', $refs);

        if ($this->_deleteAction !== null) {
            $output .= ' ON DELETE ' . $this->_deleteAction;
        }

        if ($this->_updateAction !== null) {
            $output .= ' ON UPDATE ' . $this->_updateAction;
        }

        if ($this->isVoid()) {
            $output .= ' **VOID**';
        }

        yield 'definition' => $output;
    }
}




/***************************
 * Trigger
 */
trait TConstraint_Trigger
{
    use TConstraint_Base;

    protected $_event;
    protected $_timing = opal\schema\ITriggerTiming::BEFORE;
    protected $_statements = [];

    public function __construct($name, $event, $timing, $statements)
    {
        $this->_setName($name);
        $this->setEvent($event);
        $this->setTiming($timing);
        $this->setStatements($statements);
    }

    public function setEvent($event)
    {
        if (is_string($event)) {
            switch (strtoupper($event)) {
                case 'INSERT':
                    $event = opal\schema\ITriggerEvent::INSERT;
                    break;

                case 'UPDATE':
                    $event = opal\schema\ITriggerEvent::UPDATE;
                    break;

                case 'DELETE':
                    $event = opal\schema\ITriggerEvent::DELETE;
                    break;

                default:
                    throw Exceptional::InvalidArgument(
                        'Trigger event ' . $event . ' is not recognised'
                    );
            }
        }

        switch ($event) {
            case opal\schema\ITriggerEvent::INSERT:
            case opal\schema\ITriggerEvent::UPDATE:
            case opal\schema\ITriggerEvent::DELETE:
                break;

            default:
                throw Exceptional::InvalidArgumentn(
                    'Trigger event ' . $event . ' is not recognised'
                );
        }

        if ($event != $this->_event) {
            $this->_hasChanged = true;
        }

        $this->_event = $event;
        return $this;
    }

    public function getEvent()
    {
        return $this->_event;
    }

    public function getEventName()
    {
        switch ($this->_event) {
            case opal\schema\ITriggerEvent::INSERT:
                return 'INSERT';

            case opal\schema\ITriggerEvent::UPDATE:
                return 'UPDATE';

            case opal\schema\ITriggerEvent::DELETE:
                return 'DELETE';
        }
    }

    public function setTiming($timing)
    {
        if ($timing == opal\schema\ITriggerTiming::INSTEAD_OF || strtoupper((string)$timing) == 'INSTEAD OF') {
            $timing = opal\schema\ITriggerTiming::INSTEAD_OF;
        } elseif ($timing == opal\schema\ITriggerTiming::AFTER || strtoupper((string)$timing) == 'AFTER') {
            $timing = opal\schema\ITriggerTiming::AFTER;
        } else {
            $timing = opal\schema\ITriggerTiming::BEFORE;
        }

        $this->_timing = $timing;
        return $this;
    }

    public function getTiming()
    {
        return $this->_timing;
    }

    public function getTimingName()
    {
        switch ($this->_timing) {
            case opal\schema\ITriggerTiming::BEFORE:
                return 'BEFORE';

            case opal\schema\ITriggerTiming::AFTER:
                return 'AFTER';

            case opal\schema\ITriggerTiming::INSTEAD_OF:
                return 'INSTEAD OF';
        }
    }

    public function setStatements($statements)
    {
        $this->_statements = [];

        if (!is_array($statements)) {
            $statements = [$statements];
        }

        foreach ($statements as $statement) {
            $this->addStatement($statement);
        }

        return $this;
    }

    public function addStatement($statement)
    {
        $statement = trim($statement, ' ;');

        if (!empty($statement)) {
            $this->_hasChanged = true;
            $this->_statements[] = $statement;
        }

        return $this;
    }

    public function getStatements()
    {
        return $this->_statements;
    }

    public function hasFieldReference($fields)
    {
        if (!is_array($fields)) {
            $fields = [$fields];
        }

        foreach ($fields as $i => $field) {
            if ($field instanceof opal\schema\IField) {
                $field = $field->getName();
            }

            $fields[$i] = (string)$field;
        }

        return $this->_hasFieldReference($fields);
    }

    abstract protected function _hasFieldReference(array $fields);


    // Ext. serialize
    public function toStorageArray()
    {
        return $this->_getGenericStorageArray();
    }

    protected function _getGenericStorageArray()
    {
        return [
            'nam' => $this->_name,
            'evt' => $this->_event,
            'tim' => $this->_timing,
            'stm' => $this->_statements
        ];
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        $output = $this->_name;
        $output .= ' ' . $this->getTimingName();
        $output .= ' ' . $this->getEventName() . ' ' . implode('; ', $this->_statements);

        yield 'definition' => $output;
    }
}
