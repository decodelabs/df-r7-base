<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\result;

use df;
use df\core;
use df\opal;

class OutputManifest implements IOutputManifest {
    
    protected $_primarySource;
    protected $_sources = array();
    protected $_wildcards = array();
    protected $_aggregateFields = array();
    protected $_outputFields = array();
    protected $_privateFields = array();
    protected $_fieldProcessors = null;
    
    public function __construct(opal\query\ISource $source, array $rows=null, $isNormalized=true) {
        $this->importSource($source, $rows, $isNormalized);
    }
    
    public function getPrimarySource() {
        return $this->_primarySource;
    }
    
    public function getSources() {
        return $this->_sources;
    }
    
    public function importSource(opal\query\ISource $source, array $rows=null, $isNormalized=true) {
        if($source === $this->_primarySource) {
            return $this;
        }
        
        $sourceAlias = $source->getAlias();
        $this->_sources[$sourceAlias] = $source;
        
        if(!$this->_primarySource) {
            $this->_primarySource = $source;
        }

        foreach($source->getOutputFields() as $alias => $field) {
            $this->addOutputField($field);
        }
        
        foreach($source->getPrivateFields() as $qName => $field) {
            $this->_privateFields[$qName] = $field;
        }
        
        if(isset($this->_wildcards[$sourceAlias], $rows[0])) {
            unset($this->_wildcards[$sourceAlias]);
            
            foreach($rows[0] as $key => $value) {
                $parts = explode('.', $key);
                $alias = $fieldName = array_pop($parts);
                $s = array_shift($parts);
                
                if($s == $sourceAlias 
                && substr($fieldName, 0, 1) != '@'
                //&& !isset($this->_outputFields[$alias])
                ) {
                    $this->addOutputField(new opal\query\field\Intrinsic($source, $fieldName, $alias));
                }
            }
        }
        
        return $this;
    }
    
    
    public function addOutputField(opal\query\IField $field) {
        if($field instanceof opal\query\IWildcardField) {
            $this->_wildcards[$field->getSourceAlias()] = true;
            return $this;
        }
        
        $alias = $field->getAlias();
        $qName = $field->getQualifiedName();
        $this->_outputFields[$qName] = $field;
        
        if($field instanceof opal\query\IAggregateField) {
            $this->_aggregateFields[$alias] = $qName;
        }

        if($field instanceof opal\query\ICorrelationField) {
            if($aggregateField = $field->getAggregateOutputField()) {
                $this->_aggregateFields[$aggregateField->getAlias()] = $aggregateField;
            }
        }
        
        return $this;
    }

    public function getOutputFields() {
        return $this->_outputFields;
    }
    
    public function getPrivateFields() {
        return $this->_privateFields;
    }
    
    public function getAllFields() {
        return array_merge($this->_outputFields, $this->_privateFields);
    }
    
    public function getWildcardMap() {
        return $this->_wildcards;
    }
    
    public function getAggregateFieldAliases() {
        return $this->_aggregateFields;
    }
    
    public function hasAggregateFields() {
        return !empty($this->_aggregateFields);
    }
    
    public function getOutputFieldProcessors() {
        if($this->_fieldProcessors === null) {
            $this->_fieldProcessors = array();
            
            foreach($this->_sources as $source) {
                $sourceAlias = $source->getAlias();
                $adapter = $source->getAdapter();
                
                if($adapter instanceof opal\query\IIntegralAdapter) {
                    $fieldProcessors = $adapter->getQueryResultValueProcessors(array_keys($source->getOutputFields()));
                    
                    if(!empty($fieldProcessors)) {
                        foreach($fieldProcessors as $name => $fieldProcessor) {
                            if(!$fieldProcessor instanceof opal\query\IFieldValueProcessor) {
                                continue;
                            }
                            
                            $this->_fieldProcessors[$sourceAlias.'.'.$name] = $fieldProcessor;
                        }
                    }
                }
            }
        }
        
        return $this->_fieldProcessors;
    }
}
