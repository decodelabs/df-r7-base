<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\schema;

use df;
use df\core;
use df\axis;
use df\opal;

use DecodeLabs\Glitch;
use DecodeLabs\Glitch\Dumpable;
use DecodeLabs\Exceptional;

class Base implements ISchema, Dumpable
{
    use opal\schema\TSchema;
    use opal\schema\TSchema_FieldProvider;
    use opal\schema\TSchema_IndexProvider;
    use opal\schema\TSchema_IndexedFieldProvider;

    protected $_version = 0;
    protected $_unitId;
    protected $_unitType;
    protected $_requiresTransactions = true;

    protected $_options = [
        'name' => null,
        'comment' => null
    ];


    public function __construct(axis\ISchemaBasedStorageUnit $unit, $name)
    {
        $this->_unitType = $unit->getUnitType();
        $this->_unitId = $unit->getUnitId();

        $this->setName($name);
    }

    public function getUnitType()
    {
        return $this->_unitType;
    }

    public function getUnitId()
    {
        return $this->_unitId;
    }

    public function iterateVersion()
    {
        $this->_version++;
        return $this;
    }

    public function getVersion(): int
    {
        return $this->_version;
    }

    public function hasChanged()
    {
        return $this->_hasOptionChanges()
            || $this->_hasFieldChanges()
            || $this->_hasIndexChanges();
    }

    public function markAsChanged()
    {
        Glitch::incomplete();
    }

    public function acceptChanges()
    {
        $this->_acceptOptionChanges();
        $this->_acceptFieldChanges();
        $this->_acceptIndexChanges();

        return $this;
    }

    public function _createField($name, $type, array $args)
    {
        return axis\schema\field\Base::factory($this, $name, $type, $args);
    }

    public function _createFieldFromStorageArray(array $data)
    {
        return axis\schema\field\Base::fromStorageArray($this, $data);
    }


    public function _createIndex($name, $fields=null)
    {
        return new axis\schema\constraint\Index($this, $name, $fields);
    }

    protected function _validateIndex(opal\schema\IIndex $index)
    {
        $fields = $index->getFields();

        foreach ($fields as $name => $field) {
            if ($field instanceof opal\schema\INullPrimitiveField) {
                throw Exceptional::Runtime(
                    'Indexes cannot be defined for NullPrimitive fields ('.$this->getName().'.'.$name.')'
                );
            }
        }

        return true;
    }


    public function _createIndexFromStorageArray(array $data)
    {
        return axis\schema\constraint\Index::fromStorageArray($this, $data);
    }



    public function requiresTransactions(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_requiresTransactions = $flag;
            return $this;
        }

        return $this->_requiresTransactions;
    }



    // Validation
    public function sanitize(axis\ISchemaBasedStorageUnit $unit)
    {
        foreach ($this->_fields as $field) {
            if ($field instanceof axis\schema\IField) {
                $field->sanitize($unit, $this);
            }

            if ($field instanceof axis\schema\IAutoIndexField) {
                $newName = $field->getName();
                $oldName = $this->getOriginalFieldNameFor($newName);

                if ($newName != $oldName) {
                    $this->renameIndex($oldName, $newName);
                }

                if (!$field->shouldBeIndexed()) {
                    $this->removeIndex($newName);
                    continue;
                }

                if (!$index = $this->getIndex($newName)) {
                    $index = $this->addIndex($newName, $field);
                }

                if ($field instanceof axis\schema\IAutoUniqueField) {
                    $unique = $field->shouldBeUnique();
                    $index->isUnique($unique);

                    if (!$unique) {
                        continue;
                    }
                }

                if ($field instanceof axis\schema\IAutoPrimaryField) {
                    if (!$field->shouldBePrimary()) {
                        if ($this->getPrimaryIndex() === $index) {
                            $this->setPrimaryIndex(null);
                        }

                        continue;
                    }

                    $this->setPrimaryIndex($index);
                }
            }
        }

        return $this;
    }

    public function validate(axis\ISchemaBasedStorageUnit $unit)
    {
        foreach ($this->_fields as $field) {
            if ($field instanceof axis\schema\IField) {
                $field->validate($unit, $this);
            }
        }

        return $this;
    }



    // Fields
    public function getPrimitiveFieldNames()
    {
        $output = [];

        foreach ($this->_fields as $field) {
            if ($field instanceof opal\schema\IMultiPrimitiveField) {
                $names = $field->getPrimitiveFieldNames();

                if (!empty($names)) {
                    foreach ($names as $name) {
                        $output[] = $name;
                    }
                }
            } elseif ($field instanceof opal\schema\INullPrimitiveField) {
                continue;
            } else {
                $output[] = $field->getName();
            }
        }

        return $output;
    }


    // Ext. Serialize
    public static function fromJson(opal\schema\ISchemaContext $unit, $json)
    {
        if (!$data = json_decode($json, true)) {
            throw Exceptional::Runtime(
                'Invalid json schema representation'
            );
        }

        if (!$unit instanceof axis\ISchemaBasedStorageUnit) {
            throw Exceptional::Logic(
                'Schema context is not a storage unit', null, $unit
            );
        }

        $output = new self($unit, $unit->getUnitName());
        $output->_version = $data['vsn'];
        $output->_unitType = $data['utp'];
        $output->_unitId = $data['uid'];
        $output->_requiresTransactions = (bool)($data['rtr'] ?? true);

        $output->_setGenericStorageArray($data);
        $output->_setFieldStorageArray($data);
        $output->_setIndexStorageArray($data);

        $output->acceptChanges();

        return $output;
    }

    public function toStorageArray()
    {
        $output = [
            'vsn' => $this->_version,
            'utp' => $this->_unitType,
            'uid' => $this->_unitId
        ];

        if (!$this->_requiresTransactions) {
            $output['rtr'] = false;
        }

        return array_merge(
            $output,
            $this->_getGenericStorageArray(),
            $this->_getFieldStorageArray(),
            $this->_getIndexStorageArray()
        );
    }
}
