<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;
use df\axis;

class Source implements ISource, core\IDumpable {

    use TQuery_AdapterAware;

    protected $_outputFields = [];
    protected $_privateFields = [];

    protected $_keyField = null;

    protected $_alias;
    protected $_isPrimary = false;
    private $_id;

    public function __construct(IAdapter $adapter, $alias) {
        $this->_adapter = $adapter;
        $this->_alias = $alias;
    }

    public function getAlias() {
        return $this->_alias;
    }

    public function getId() {
        if(!$this->_id) {
            $this->_id = $this->_adapter->getQuerySourceId();
        }

        return $this->_id;
    }

    public function getUniqueId() {
        return $this->getId().' as '.$this->getAlias();
    }

    public function getHash() {
        return $this->_adapter->getQuerySourceAdapterHash();
    }

    public function getDisplayName() {
        return $this->_adapter->getQuerySourceDisplayName();
    }

    public function isDerived() {
        return $this->_adapter instanceof IDerivedSourceAdapter;
    }

    public function isPrimary(bool $flag=null) {
        if($flag !== null) {
            $this->_isPrimary = $flag;
            return $this;
        }

        return $this->_isPrimary;
    }

    public function handleQueryException(IQuery $query, \Throwable $e) {
        if($this->_adapter->handleQueryException($query, $e)) {
            return true;
        }

        return false;
    }



// Fields
    public function extrapolateIntegralAdapterField($name, $alias=null, opal\schema\IField $field=null) {
        if($this->isDerived()) {
            return $this->getAdapter()
                ->getDerivationSource()
                ->extrapolateIntegralAdapterField($name, $alias, $field)
                ->rewriteAsDerived($this);
        }

        if($this->_adapter instanceof IIntegralAdapter) {
            // Get primary
            if($name == '@primary') {
                $schema = $this->_adapter->getQueryAdapterSchema();

                if(!$primaryIndex = $schema->getPrimaryIndex()) {
                    throw new opal\schema\RuntimeException(
                        'Unit '.$this->_adapter->getUnitId().' does not have a primary index'
                    );
                }

                $fields = [];

                foreach($primaryIndex->getFields() as $fieldName => $indexField) {
                    $subField = $this->extrapolateIntegralAdapterFieldFromSchemaField($fieldName, $fieldName, $indexField);

                    foreach($subField->dereference() as $innerField) {
                        $fields[] = $innerField;
                    }
                }

                return new opal\query\field\Virtual($this, $name, $alias, $fields);
            }


            // Raw sql field
            if(substr($name, 0, 5) == '@raw ') {
                $expression = trim(substr($name, 5));
                return new opal\query\field\Raw($this, $expression, $alias);
            }


            // Get name
            if($name == '@name') {
                $name = $this->_adapter->getRecordNameField();
                return new opal\query\field\Intrinsic($this, $name, $alias);
            }


            if(substr($name, 0, 1) == '@') {
                throw new axis\schema\RuntimeException(
                    'Unknown symbolic field: '.$name
                );
            }

            if($alias === null) {
                $alias = $name;
            }


            // Dereference from source manager
            if($field === null) {
                $schema = $this->_adapter->getQueryAdapterSchema();

                if(!$field = $schema->getField($name)) {
                    return new opal\query\field\Intrinsic($this, $name, $alias);
                }
            }

            // Generic
            return $this->extrapolateIntegralAdapterFieldFromSchemaField($name, $alias, $field);
        } else if($this->_adapter instanceof INaiveIntegralAdapter) {
            if($name == '@primary') {
                if(!$primaryIndex = $this->_adapter->getPrimaryIndex()) {
                    throw new axis\schema\RuntimeException(
                        'Unit '.$this->getUnitId().' does not have a primary index'
                    );
                }

                $fields = [];

                foreach($primaryIndex->getFields() as $fieldName => $indexField) {
                    $fields[] = new opal\query\field\Intrinsic($this, $fieldName, $fieldName);
                }

                return new opal\query\field\Virtual($this, $name, $alias, $fields);
            }

            if(substr($name, 0, 1) == '@') {
                throw new axis\schema\RuntimeException(
                    'Unknown symbolic field: '.$name
                );
            }

            if($alias === null) {
                $alias = $name;
            }

            // Generic
            return $this->extrapolateIntegralAdapterFieldFromSchemaField($name, $alias, $field);
        }
    }

    public function extrapolateIntegralAdapterFieldFromSchemaField($name, $alias, opal\schema\IField $field=null) {
        if($field instanceof opal\schema\IMultiPrimitiveField) {
            $privateFields = [];

            foreach($field->getPrimitiveFieldNames() as $fieldName) {
                $privateFields[] = new opal\query\field\Intrinsic($this, $fieldName, $alias);
            }

            $output = new opal\query\field\Virtual($this, $name, $alias, $privateFields);
        } else if($field instanceof opal\schema\INullPrimitiveField) {
            $output = new opal\query\field\Virtual($this, $name, $alias);
        } else {
            $output = new opal\query\field\Intrinsic($this, $name, $alias);
        }

        return $output;
    }


    public function getFieldProcessor(IIntrinsicField $field) {
        if(!$this->_adapter instanceof IIntegralAdapter) {
            return null;
        }

        return $this->_adapter->getQueryAdapterSchema()->getField($field->getName());
    }


    public function addOutputField(opal\query\IField $field) {
        foreach($this->_prepareField($field) as $field) {
            $alias = $field->getAlias();
            $this->_outputFields[$alias] = $field;
            unset($this->_privateFields[$field->getAlias()]);
        }

        return $this;
    }

    public function addPrivateField(opal\query\IField $field) {
        foreach($this->_prepareField($field) as $field) {
            if(!in_array($field, $this->_outputFields, true)) {
                $this->_privateFields[$field->getAlias()] = $field;
            }
        }

        return $this;
    }

    protected function _prepareField(opal\query\IField $field) {
        $fields = [];

        if($field instanceof opal\query\IVirtualField) {
            if(substr($field->getName(), 0, 1) == '@') {
                $fields = $field->getTargetFields();
            }
        } else if($field instanceof opal\query\IWildcardField
        && $this->_adapter instanceof IIntegralAdapter) {
            $muteFields = $field->getMuteFields();

            foreach($this->_adapter->getQueryAdapterSchema()->getFields() as $name => $queryField) {
                if($queryField instanceof opal\schema\INullPrimitiveField) {
                    continue;
                }

                $alias = null;

                if(array_key_exists($name, $muteFields)) {
                    if(null === ($alias = $muteFields[$name])) {
                        continue;
                    }
                }

                $field = $this->extrapolateIntegralAdapterField($name, $alias, $queryField);
                $field->isFromWildcard(true);
                $qName = $field->getQualifiedName();

                if($field) {
                    foreach($this->_outputFields as $outField) {
                        if($outField->getQualifiedName() == $qName) {
                            continue 2;
                        }
                    }

                    $fields[] = $field;
                }
            }
        }

        if(empty($fields)) {
            $fields[] = $field;
        }

        return $fields;
    }

    public function removeWildcardOutputField($name, $alias=null) {
        if(!isset($this->_outputFields[$name])) {
            return false;
        }

        if(!$this->_outputFields[$name]->isFromWildcard()) {
            return false;
        }

        if($alias === null) {
            unset($this->_outputFields[$name]);
        } else {
            $this->_outputFields[$name]->setAlias($alias)->isFromWildcard(false);
        }

        return true;
    }

    public function getFieldByAlias($alias) {
        if(isset($this->_outputFields[$alias])) {
            return $this->_outputFields[$alias];
        } else if(isset($this->_privateFields[$alias])) {
            return $this->_privateFields[$alias];
        }

        return null;
    }

    public function getFieldByQualifiedName($qName) {
        foreach($this->_outputFields as $field) {
            if($qName == $field->getQualifiedName()) {
                return $field;
            }
        }

        foreach($this->_privateFields as $field) {
            if($qName == $field->getQualifiedName()) {
                return $field;
            }
        }

        return null;
    }

    public function getFirstOutputDataField() {
        foreach($this->_outputFields as $alias => $field) {
            if($field instanceof opal\query\IWildcardField) {
                continue;
            }

            return $field;
        }
    }

    public function getLastOutputDataField() {
        $t = $this->_outputFields;

        while(!empty($t)) {
            $output = array_pop($t);

            if(!$output instanceof opal\query\IWildcardField) {
                return $output;
            }
        }
    }


    public function setKeyField(IField $field=null) {
        $this->_keyField = $field;
        return $this;
    }

    public function getKeyField() {
        return $this->_keyField;
    }



    public function getOutputFields() {
        return $this->_outputFields;
    }

    public function getDereferencedOutputFields() {
        $output = [];

        foreach($this->_outputFields as $mainField) {
            foreach($mainField->dereference() as $field) {
                $output[$field->getQualifiedName()] = $field;
            }
        }

        if($this->_keyField) {
            $output[$this->_keyField->getQualifiedName()] = $this->_keyField;
        }

        return $output;
    }

    public function isOutputField(IField $field) {
        return isset($this->_outputFields[$field->getAlias()]);
    }

    public function getPrivateFields() {
        return $this->_privateFields;
    }

    public function getDereferencedPrivateFields() {
        $output = [];

        foreach($this->_privateFields as $mainField) {
            foreach($mainField->dereference() as $field) {
                $output[$field->getQualifiedName()] = $field;
            }
        }

        return $output;
    }

    public function getAllFields() {
        return array_merge($this->_outputFields, $this->_privateFields);
    }

    public function getAllDereferencedFields() {
        return array_merge($this->getDereferencedOutputFields(), $this->getDereferencedPrivateFields());
    }

    public function hasWildcardField() {
        foreach($this->_outputFields as $field) {
            if($field instanceof opal\query\IWildcardField) {
                return true;
            }
        }

        return false;
    }

    public function getWildcardField() {
        foreach($this->_outputFields as $field) {
            if($field instanceof opal\query\IWildcardField) {
                return $field;
            }
        }

        return null;
    }


// Dump
    public function getDumpProperties() {
        $output = [
            '__id' => new core\debug\dumper\Property(
                'sourceId', $this->getId(), 'protected'
            )
        ];

        foreach($this->_outputFields as $alias => $field) {
            $output[$alias] = $field->getQualifiedName();
        }

        foreach($this->_privateFields as $alias => $field) {
            $output[$alias] = new core\debug\dumper\Property(
                $alias, $field->getQualifiedName(), 'private'
            );
        }

        return $output;
    }
}
