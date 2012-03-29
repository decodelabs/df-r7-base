<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema;

use df;
use df\core;
use df\opal;

abstract class Base implements ISchema, core\IDumpable {
    
    use opal\schema\TSchema;
    use opal\schema\TSchema_FieldProvider;
    use opal\schema\TSchema_IndexProvider;
    use opal\schema\TSchema_ForeignKeyProvider;
    use opal\schema\TSchema_TriggerProvider;
    
    protected $_adapter;
    
    protected $_options = [
        'name' => null,
        'comment' => null,
        'isTemporary' => false
    ];
    
    public function __construct(opal\rdbms\IAdapter $adapter, $name) {
        $this->_adapter = $adapter;
        $this->setName($name);
    }
    
    public function getAdapter() {
        return $this->_adapter;
    }
    
    public function getTable() {
        return $this->_adapter->getTable($this->getName());
    }
    
    public function getSqlVariant() {
        return $this->_adapter->getServerType();
    }
    
    public function isTemporary($flag=null) {
        if($flag !== null) {
            return $this->setOption('isTemporary', (bool)$flag);
        }
        
        return (bool)$this->getOption('isTemporary');
    }
    
    public function normalize() {
        foreach($this->getFieldsToRemove() as $field) {
            foreach($this->_foreignKeys as $name => $key) {
                if($key->hasField($field)) {
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
    public function hasChanged() {
        return $this->_hasOptionChanges()
            || $this->_hasFieldChanges()
            || $this->_hasIndexChanges()
            || $this->_hasForeignKeyChanges()
            || $this->_hasTriggerChanges();
    }

    public function acceptChanges() {
        $this->_acceptOptionChanges();
        $this->_acceptFieldChanges();
        $this->_acceptIndexChanges();
        $this->_acceptForeignKeyChanges();
        $this->_acceptTriggerChanges();
        
        return $this;
    }
    
    
    
// Creators
    protected function _createField($name, $type, array $args) {
        return opal\rdbms\schema\field\Base::factory(
            $this, $type, $name, $args
        );
    }
    
    protected function _createIndex($name, $fields=null) {
        return new opal\rdbms\schema\constraint\Index($this, $name, $fields);
    }
    
    protected function _createForeignKey($name, $targetSchema) {
        return new opal\rdbms\schema\constraint\ForeignKey($this, $name, $targetSchema);
    }
    
    protected function _createTrigger($name, $event, $timing, $statement) {
        return new opal\rdbms\schema\constraint\Trigger($this, $name, $event, $timing, $statement);
    }
}
