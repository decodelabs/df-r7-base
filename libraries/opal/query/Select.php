<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Select implements ISelectQuery, core\IDumpable {
            
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Locational;
    use TQuery_Distinct;
    use TQuery_Correlatable;
    use TQuery_Joinable;
    use TQuery_Attachable;
    use TQuery_Populatable;
    use TQuery_Combinable;
    use TQuery_PrerequisiteClauseFactory;
    use TQuery_PrerequisiteAwareWhereClauseFactory;
    use TQuery_Groupable;
    use TQuery_HavingClauseFactory;
    use TQuery_Orderable;
    use TQuery_Limitable;
    use TQuery_Offsettable;
    use TQuery_Pageable;
    use TQuery_Read;
    
    public function __construct(ISourceManager $sourceManager, ISource $source) {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
    }
    
    public function getQueryType() {
        return IQueryTypes::SELECT;
    }
    
    
// Sources    
    public function addOutputFields($fields) {
        if(!is_array($fields)) {
            $fields = func_get_args();
        }
        
        foreach($fields as $field) {
            $this->_sourceManager->extrapolateOutputField($this->_source, $field);
        }
        
        return $this;
    }
    
    

    
// Output
    public function count() {
        return $this->_sourceManager->executeQuery($this, function($adapter) {
            return (int)$adapter->countSelectQuery($this);
        });
    }
    
    public function toList($valField1, $valField2=null) {
        if($valField2 !== null) {
            $keyField = $valField1;
            $valField = $valField2;
        } else {
            $keyField = null;
            $valField = $valField1;
        }
        
        $data = $this->_fetchSourceData($keyField, $valField);
        
        if($data instanceof core\IArrayProvider) {
            $data = $data->toArray();
        }
        
        if(!is_array($data)) {
            throw new UnexpectedValueException(
                'Source did not return a result that could be converted to an array'
            );
        }
        
        return $data;
    }
    
    public function toValue($valField=null) {
        if($valField !== null) {
            $valField = $this->_sourceManager->extrapolateDataField($this->_source, $valField);
            $data = $this->toRow();
            
            $key = $valField->getAlias();
            
            if(isset($data[$key])) {
                return $data[$key];
            } else {
                return null;
            }
        } else {
            if(null !== ($data = $this->toRow())) {
                return array_shift($data);
            }
            
            return null;
        }
    }
    
    protected function _fetchSourceData($keyField=null, $valField=null) {
        if($keyField !== null) {
            $keyField = $this->_sourceManager->extrapolateDataField($this->_source, $keyField);
        }
        
        if($valField !== null) {
            if(isset($this->_attachments[$valField])) {
                $valField = new opal\query\field\Attachment($valField, $this->_attachments[$valField]);
            } else {
                $valField = $this->_sourceManager->extrapolateDataField($this->_source, $valField);
            }
        }
        
        $output = $this->_sourceManager->executeQuery($this, function($adapter) {
            return $adapter->executeSelectQuery($this);
        });

        $output = $this->_createBatchIterator($output, $keyField, $valField);

        if($this->_paginator && $this->_offset == 0 && $this->_limit) {
            $count = count($output);

            if($count < $this->_limit) {
                $this->_paginator->setTotal($count);
            }
        }

        return $output;
    }
    
// Dump
    public function getDumpProperties() {
        $output = [
            'sources' => $this->_sourceManager,
            'fields' => $this->_source
        ];
        
        if(!empty($this->_populates)) {
            $output['populates'] = $this->_populates;
        }

        if(!empty($this->_combines)) {
            $output['combines'] = $this->_combines;
        }

        if(!empty($this->_joins)) {
            $output['join'] = $this->_joins;
        }
        
        if(!empty($this->_attachments)) {
            $output['attach'] = $this->_attachments;
        }
        
        if($this->hasWhereClauses()) {
            $output['where'] = $this->getWhereClauseList();
        }
        
        if(!empty($this->_group)) {
            $output['group'] = $this->_groups;
        }
        
        if($this->hasHavingClauses()) {
            $output['having'] = $this->_havingClauseList;
        }
        
        if(!empty($this->_order)) {
            $order = array();
            
            foreach($this->_order as $directive) {
                $order[] = $directive->toString();
            }
            
            $output['order'] = implode(', ', $order);
        }
        
        if($this->_limit) {
            $output['limit'] = $this->_limit;
        }
        
        if($this->_offset) {
            $output['offset'] = $this->_offset;
        }
        
        return $output;
    }
}
    