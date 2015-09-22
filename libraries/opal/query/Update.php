<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Update implements IUpdateQuery, core\IDumpable {
    
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_Locational;
    use TQuery_PrerequisiteClauseFactory;
    use TQuery_WhereClauseFactory;
    use TQuery_Orderable;
    use TQuery_Limitable;

    protected $_values = [];
    protected $_preparedValues;
    
    public function __construct(ISourceManager $sourceManager, ISource $source, array $valueMap=null) {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
        
        if($valueMap !== null) {
            $this->set($valueMap);
        }
    }
    
    public function getQueryType() {
        return IQueryTypes::UPDATE;
    }
    
    public function set($key, $value=null) {
        if(is_array($key)) {
            $values = $key;
        } else {
            $values = [$key => $value];
        }
        
        $this->_values = array_merge($this->_values, $values);
        $this->_preparedValues = null;
        return $this;
    }
    
    public function express($field, $var1) {
        return call_user_func_array([$this, 'beginExpression'], func_get_args())->endExpression();
    }

    public function beginExpression($field, $var1) {
        return new Expression($this, $field, array_slice(func_get_args(), 1));
    }

    public function expressCorrelation($field, $targetField) {
        core\stub($field, $targetField);
    }

    
    public function getValues() {
        return $this->_values;
    }
    
    public function getPreparedValues() {
        if(!$this->_preparedValues) {
            $this->_preparedValues = $this->_deflateUpdateValues($this->_values);
        }

        return $this->_preparedValues;
    }
    
    
// Execute
    public function execute() {
        $adapter = $this->_source->getAdapter();
        
        if(empty($this->_values)) {
            return 0;
        }
        
        $output = $this->_sourceManager->executeQuery($this, function($adapter) {
            return $adapter->executeUpdateQuery($this);
        });

        $this->_preparedValues = null;
        return $output;
    }

    protected function _deflateUpdateValues(array $values) {
        $adapter = $this->_source->getAdapter();

        if(!$adapter instanceof IIntegralAdapter) {
            return $values;
        }

        $schema = $adapter->getQueryAdapterSchema();
        
        foreach($values as $name => $value) {
            if($value instanceof IExpression) {
                continue;
            }

            if(!$field = $schema->getField($name)) {
                continue;
            }
            
            if($field instanceof opal\schema\INullPrimitiveField) {
                unset($values[$name]);
                continue;
            }

            if($field instanceof opal\schema\IAutoTimestampField 
            && ($value === null || $value === '') 
            && !$field->isNullable()) {
                $value = new core\time\Date();
            }
            
            $value = $field->deflateValue($field->sanitizeValue($value));
            
            if(is_array($value)) {
                unset($values[$name]);
                
                foreach($value as $key => $val) {
                    $values[$key] = $val;
                }
            } else {
                $values[$name] = $value;
            }
        }
        
        return $values;
    }
    
    
    
// Dump
    public function getDumpProperties() {
        $output = [
            'source' => $this->_source->getAdapter(),
            'values' => $this->_values
        ];
        
        if($this->hasWhereClauses()) {
            $output['where'] = $this->getWhereClauseList();
        }
        
        if(!empty($this->_order)) {
            $order = [];
            
            foreach($this->_order as $directive) {
                $order[] = $directive->toString();
            }
            
            $output['order'] = implode(', ', $order);
        }
        
        if($this->_limit !== null) {
            $output['limit'] = $this->_limit;
        }
        
        return $output;
    }
}
