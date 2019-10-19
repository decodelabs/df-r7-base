<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema;

use df;
use df\core;
use df\opal;
use df\mesh;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

abstract class Base implements ISchema, Inspectable
{
    use opal\schema\TSchema;
    use opal\schema\TSchema_FieldProvider;
    use opal\schema\TSchema_IndexProvider;
    use opal\schema\TSchema_IndexedFieldProvider;
    use opal\schema\TSchema_ForeignKeyProvider;
    use opal\schema\TSchema_TriggerProvider;

    protected $_adapter;

    protected $_options = [
        'name' => null,
        'comment' => null,
        'isTemporary' => false
    ];

    public function __construct(opal\rdbms\IAdapter $adapter, $name)
    {
        $this->_adapter = $adapter;
        $this->setName($name);
    }

    public function getAdapter()
    {
        return $this->_adapter;
    }

    public function getTable()
    {
        return $this->_adapter->getTable($this->getName());
    }

    public function getSqlVariant()
    {
        return $this->_adapter->getServerType();
    }

    public function isTemporary(bool $flag=null)
    {
        if ($flag !== null) {
            return $this->setOption('isTemporary', $flag);
        }

        return (bool)$this->getOption('isTemporary');
    }

    public function normalize()
    {
        foreach ($this->getFieldsToRemove() as $field) {
            foreach ($this->_foreignKeys as $name => $key) {
                if ($key->hasField($field)) {
                    throw new opal\rdbms\ConstraintException(
                        'Foreign key '.$key->getName().' requires to-be-dropped field '.$field->getName().'. '.
                        'You should either not drop this field, or drop this key first'
                    );
                }
            }
        }

        return $this;
    }


    // Changes
    public function hasChanged()
    {
        return $this->_hasOptionChanges()
            || $this->_hasFieldChanges()
            || $this->_hasIndexChanges()
            || $this->_hasForeignKeyChanges()
            || $this->_hasTriggerChanges();
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
        $this->_acceptForeignKeyChanges();
        $this->_acceptTriggerChanges();

        return $this;
    }



    // Creators
    public function _createField($name, $type, array $args)
    {
        return opal\rdbms\schema\field\Base::factory(
            $this, $type, $name, $args
        );
    }

    public function _createFieldFromStorageArray(array $data)
    {
        Glitch::incomplete($data);
    }

    public function _createIndex($name, $fields=null)
    {
        return new opal\rdbms\schema\constraint\Index($this, $name, $fields);
    }

    public function _createIndexFromStorageArray(array $data)
    {
        Glitch::incomplete($data);
    }

    public function _createForeignKey($name, $targetSchema)
    {
        return new opal\rdbms\schema\constraint\ForeignKey($this, $name, $targetSchema);
    }

    public function _createForeignKeyFromStorageArray(array $data)
    {
        Glitch::incomplete($data);
    }

    public function _createTrigger($name, $event, $timing, $statement)
    {
        return new opal\rdbms\schema\constraint\Trigger($this, $name, $event, $timing, $statement);
    }

    public function _createTriggerFromStorageArray(array $data)
    {
        Glitch::incomplete($data);
    }


    // Ext. serialize
    public static function fromJson(opal\schema\ISchemaContext $schemaContext, $json)
    {
        if (!$data = json_decode($json, true)) {
            throw new opal\rdbms\RuntimeException(
                'Invalid json schema representation'
            );
        }

        Glitch::incomplete($data);
    }


    public function toStorageArray()
    {
        return array_merge(
            $this->_getGenericStorageArray(),
            $this->_getFieldStorageArray(),
            $this->_getIndexStorageArray(),
            $this->_getForeignKeyStorageArray(),
            $this->_getTriggerStorageArray()
        );
    }

    // Mesh
    public function getEntityLocator()
    {
        $output = $this->_adapter->getEntityLocator();
        $output->addNode(null, 'Schema', $this->getName());
        return $output;
    }
}
