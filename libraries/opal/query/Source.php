<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Source implements ISource, core\IDumpable {
    
    use TQuery_AdapterAware;
    
    protected $_outputFields = array();
    protected $_privateFields = array();

    protected $_alias;
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
    
    public function handleQueryException(IQuery $query, \Exception $e) {
        if($this->_adapter->handleQueryException($query, $e)) {
            return true;
        }

        return false;
    }
    
    
    
// Capabilities
    public function testWhereClauseSupport() {
        if(!$this->_adapter->supportsQueryFeature(IQueryFeatures::WHERE_CLAUSE)) {
            throw new LogicException(
                'Query adapter '.$this->_adapter->getQuerySourceDisplayName().' '.
                'does not support WHERE clauses'
            );
        }
    }
    
    public function testGroupDirectiveSupport() {
        if(!$this->_adapter->supportsQueryFeature(IQueryFeatures::GROUP_DIRECTIVE)) {
            throw new LogicException(
                'Query adapter '.$this->_adapter->getQuerySourceDisplayName().' '.
                'does not support GROUP directives'
            );
        }
    }
    
    public function testAggregateClauseSupport() {
        if(!$this->_adapter->supportsQueryFeature(IQueryFeatures::HAVING_CLAUSE)) {
            throw new LogicException(
                'Query adapter '.$this->_adapter->getQuerySourceDisplayName().' '.
                'does not support HAVING clauses'
            );
        }
    }
    
    public function testOrderDirectiveSupport() {
        if(!$this->_adapter->supportsQueryFeature(IQueryFeatures::ORDER_DIRECTIVE)) {
            throw new LogicException(
                'Query adapter '.$this->_adapter->getQuerySourceDisplayName().' '.
                'does not support ORDER directives'
            );
        }
    }
    
    public function testLimitDirectiveSupport() {
        if(!$this->_adapter->supportsQueryFeature(IQueryFeatures::LIMIT)) {
            throw new LogicException(
                'Query adapter '.$this->_adapter->getQuerySourceDisplayName().' '.
                'does not support LIMIT directives'
            );
        }
    }
    
    public function testOffsetDirectiveSupport() {
        if(!$this->_adapter->supportsQueryFeature(IQueryFeatures::OFFSET)) {
            throw new LogicException(
                'Query adapter '.$this->_adapter->getQuerySourceDisplayName().' '.
                'does not support OFFSET directives'
            );
        }
    }
    

    
    
// Fields
    public function addOutputField(opal\query\IField $field) {
        $fields = array();
        
        if($field instanceof opal\query\IVirtualField) {
            $fields = $field->getTargetFields();
        } else if($field instanceof opal\query\IWildcardField 
        && $this->_adapter instanceof IIntegralAdapter) {
            $queryFields = $this->_adapter->dereferenceQuerySourceWildcard($this);
            
            if(is_array($queryFields) && !empty($queryFields)) {
                foreach($queryFields as $queryField) {
                    if(is_string($queryField)) {
                        $queryField = new opal\query\field\Intrinsic($this, $queryField);
                    }
                    
                    $fields[] = $queryField;
                }
            }
        }
        
        if(empty($fields)) {
            $fields[] = $field;
        }

        foreach($fields as $field) {
            $alias = $field->getAlias();
            $this->_outputFields[$alias] = $field;
            unset($this->_privateFields[$field->getAlias()]);
        }
        
        return $this;
    }
    
    public function addPrivateField(opal\query\IField $field) {
        if(!in_array($field, $this->_outputFields, true)) {
            $this->_privateFields[$field->getAlias()] = $field;
        }
        
        return $this;
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
   
    public function getOutputFields() {
        return $this->_outputFields;
    }
    
    public function getDereferencedOutputFields() {
        $output = array();
        
        foreach($this->_outputFields as $mainField) {
            foreach($mainField->dereference() as $field) {
                $output[$field->getAlias()] = $field;
            }
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
        $output = array();
        
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
