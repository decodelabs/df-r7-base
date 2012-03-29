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
    
    public function __construct(IAdapter $adapter, $alias) {
        $this->_adapter = $adapter;
        $this->_alias = $alias;
    }
    
    public function getAlias() {
        return $this->_alias;
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
        
        if($field instanceof opal\query\IWildcardField 
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
            unset($this->_privateFields[$field->getQualifiedName()]);
        }
        
        return $this;
    }
    
    public function addPrivateField(opal\query\IField $field) {
        if(!isset($this->_outputFields[$field->getQualifiedName()])) {
            $this->_privateFields[$field->getQualifiedName()] = $field;
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
   
    public function getOutputFields() {
        return $this->_outputFields;
    }
    
    public function getDereferencedOutputFields() {
        $output = array();
        
        foreach($this->_outputFields as $field) {
            foreach($field->dereference() as $field) {
                $output[$field->getAlias()] = $field;
            }
        }
        
        return $output;
    }
    
    public function getPrivateFields() {
        return $this->_privateFields;
    }
    
    public function getDereferencedPrivateFields() {
        $output = array();
        
        foreach($this->_privateFields as $field) {
            foreach($field->dereference() as $field) {
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
        $output = array();
        
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
