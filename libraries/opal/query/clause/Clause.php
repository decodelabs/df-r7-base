<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\clause;

use df;
use df\core;
use df\opal;

class Clause implements opal\query\IClause, core\IDumpable {
    
    const BETWEEN_CONVERSION_THRESHOLD = 15;

    const OP_EQ = '=';
    const OP_NEQ = '!=';
    const OP_GT = '>';
    const OP_GTE = '>=';
    const OP_LT = '<';
    const OP_LTE = '<=';
    
    const OP_IN = 'in';
    const OP_NOT_IN = 'not in';
    const OP_BETWEEN = 'between';
    const OP_NOT_BETWEEN = 'not between';
    const OP_LIKE = 'like';
    const OP_NOT_LIKE = 'not like';
    const OP_CONTAINS = 'contains';
    const OP_NOT_CONTAINS = 'not contains';
    const OP_BEGINS = 'begins';
    const OP_NOT_BEGINS = 'not begins';
    const OP_ENDS = 'ends';
    const OP_NOT_ENDS = 'not ends';
    
    protected $_isOr = false;
    protected $_field;
    protected $_operator;
    protected $_value;
    protected $_preparedValue;
    protected $_hasPreparedValue = null;
    
    
    public static function factory(opal\query\IClauseFactory $parent, opal\query\IField $field, $operator, $value, $isOr=false) {
        if($value instanceof opal\query\IQuery
        && !$value instanceof opal\query\ICorrelationQuery) {
            throw new opal\query\ValueException(
                'Only correlation queries are allowed as clause values'
            );
        }

        if($field instanceof opal\query\IVirtualField) {
            return self::_virtualFactory($parent, $field, $operator, $value, $isOr);
        }
        
        return new self($field, $operator, $value, $isOr);
    }
    
    private static function _virtualFactory(opal\query\IClauseFactory $parent, opal\query\IField $field, $operator, $value, $isOr=false) {
        $source = $field->getSource();
        $adapter = $source->getAdapter();
        
        if($adapter instanceof opal\query\IIntegralAdapter) {
            $operator = self::normalizeOperator($operator);

            $output = $adapter->rewriteVirtualQueryClause(
                $parent, $field, $operator, $value, $isOr
            );
                
            if($output instanceof opal\query\IClauseProvider) {
                return $output;
            }
        }
        
        
        // Adapter couldn't handle it, will have to fudge it instead :)
        $targetFields = $field->getTargetFields();
        
        if(count($targetFields) == 1) {
            return new self(array_shift($targetFields), $operator, $value, $isOr);
        }
        
            
        if($value instanceof opal\query\ICorrelationQuery
        || $value instanceof opal\query\IField) {
            throw new opal\query\ValueException(
                'Correlation subquery or field clauses on multi-value virtual fields is not currently supported'
            );
        }
        
        
        if($adapter instanceof opal\query\IIntegralAdapter) {
            try {
                $value = $adapter->prepareQueryClauseValue(
                    $field, $value
                );
            } catch(\Exception $e) {
                throw new opal\query\ValueException(
                    'Invalid clause value - '.$e->getMessage(),
                    $e->getCode()
                );
            }
        }
        
        
        $list = new ListBase($parent, $isOr);
        
        foreach($targetFields as $targetField) {
            $targetFieldName = $targetField->getName();
            
            if(isset($value[$targetFieldName])) {
                $targetValue = $value[$targetFieldName];
            } else {
                $targetValue = null;
            }
            
            $list->_addClause(new self($targetField, $operator, $targetValue));
        }
        
        return $list;
    }
    
    protected function __construct(opal\query\IField $field, $operator, $value, $isOr=false) {
        $this->setField($field);
        $this->setOperator($operator);
        $this->setValue($value);
        $this->isOr($isOr);
    }
    
    public function isOr($flag=null) {
        if($flag !== null) {
            $this->_isOr = (bool)$flag;
            return $this;
        }
        
        return $this->_isOr;
    }
    
    public function isAnd($flag=null) {
        if($flag !== null) {
            $this->_isOr = !(bool)$flag;
            return $this;
        }
        
        return !$this->_isOr;
    }
    
    public function setField(opal\query\IField $field) {
        if($field instanceof opal\query\IVirtualField) {
            throw new opal\query\InvalidArgumentException(
                'Virtual fields cannot be used directly in clauses'
            );
        }
        
        $this->_field = $field;
        return $this;
    }
    
    public function getField() {
        return $this->_field;
    }
    
    public static function isNegatedOperator($operator) {
        if(substr($operator, 0, 1) == '!') {
            return true;
        }
        
        switch($operator) {
            case self::OP_NEQ:
            case self::OP_NOT_IN:
            case self::OP_NOT_BETWEEN:
            case self::OP_NOT_LIKE:
            case self::OP_NOT_CONTAINS:
            case self::OP_NOT_BEGINS:
            case self::OP_NOT_ENDS:
                return true;
        }
        
        return false;
    }
    
    public static function negateOperator($operator) {
        switch(self::normalizeOperator($operator)) {
            case self::OP_EQ: return self::OP_NEQ;
            case self::OP_NEQ: return self::OP_EQ;
            case self::OP_GT: return self::OP_LTE;
            case self::OP_GTE: return self::OP_LT;
            case self::OP_LT: return self::OP_GTE;
            case self::OP_LTE: return self::OP_GT;
            case self::OP_IN: return self::OP_NOT_IN;
            case self::OP_NOT_IN: return self::OP_IN;
            case self::OP_BETWEEN: return self::OP_NOT_BETWEEN;
            case self::OP_NOT_BETWEEN: return self::OP_BETWEEN;
            case self::OP_LIKE: return self::OP_NOT_LIKE;
            case self::OP_NOT_LIKE: return self::OP_LIKE;
            case self::OP_CONTAINS: return self::OP_NOT_CONTAINS;
            case self::OP_NOT_CONTAINS: return self::OP_CONTAINS;
            case self::OP_BEGINS: return self::OP_NOT_BEGINS;
            case self::OP_NOT_BEGINS: return self::OP_BEGINS;
            case self::OP_ENDS: return self::OP_NOT_ENDS;
            case self::OP_NOT_ENDS: return self::OP_ENDS;
                
            default:
                throw new opal\query\OperatorException(
                    'Operator '.$operator.' is not recognized'
                );
        }

        return $operator;
    }
    
    public static function normalizeOperator($operator) {
        $operator = strtolower($operator);
        
        if(substr($operator, 0, 1) == '!') {
            $operator = self::negateOperator(substr($operator, 1));
        } else {
            switch($operator) {
                case self::OP_EQ:
                case self::OP_NEQ:
                case self::OP_GT:
                case self::OP_GTE:
                case self::OP_LT:
                case self::OP_LTE:
                case self::OP_IN:
                case self::OP_NOT_IN:
                case self::OP_BETWEEN:
                case self::OP_NOT_BETWEEN:
                case self::OP_LIKE:
                case self::OP_NOT_LIKE:
                case self::OP_CONTAINS:
                case self::OP_NOT_CONTAINS:
                case self::OP_BEGINS:
                case self::OP_NOT_BEGINS:
                case self::OP_ENDS:
                case self::OP_NOT_ENDS:
                    break;
                    
                default:
                    throw new opal\query\OperatorException(
                        'Operator '.$operator.' is not recognized'
                    );
            }
        }

        return $operator;
    }
    
    public function setOperator($operator) {
        $this->_operator = self::normalizeOperator($operator);
        return $this;
    }
    
    public function getOperator() {
        return $this->_operator;
    }
    
    public function setValue($value) {
        if(is_array($value)) {
            $value = $this->_flattenArray($value);
            
            switch($this->_operator) {
                case self::OP_IN:
                    if(count($value) == 1) {
                        $this->_operator = self::OP_EQ;
                        $value = array_shift($value);
                    }

                    break;

                case self::OP_NOT_IN:
                    if(count($value) == 1) {
                        $this->_operator = self::OP_NEQ;
                        $value = array_shift($value);
                    }

                    break;

                case self::OP_EQ:
                    $this->_operator = self::OP_IN;
                    break;

                case self::OP_NEQ:
                    $this->_operator = self::OP_NOT_IN;
                    break;
            }
        }

        if($value instanceof opal\query\ICorrelationQuery) {
            switch($this->_operator) {
                case self::OP_EQ:
                    $this->_operator = self::OP_IN;
                    break;

                case self::OP_NEQ:
                    $this->_operator = self::OP_NOT_IN;
                    break;

                case self::OP_IN:
                case self::OP_NOT_IN:
                    break;

                default:
                    throw new opal\query\ValueException(
                        'Correlation clauses cannot use operator '.$this->_operator
                    );
            }
        } else {
            switch($this->_operator) {
                case self::OP_IN:
                case self::OP_NOT_IN:
                    if($value instanceof opal\query\IField) {
                        throw new opal\query\ValueException(
                            'Value for in operator cannot be a field reference'
                        );
                    }
                    
                    if($value instanceof core\IArrayProvider) {
                        $value = $value->toArray();
                    }
                    
                    if(!is_array($value)) {
                        $value = array((string)$value);
                    }

                    $value = $this->_flattenArray($value);
                    break;
                    
                case self::OP_BETWEEN:
                case self::OP_NOT_BETWEEN:
                    if($value instanceof opal\query\IField) {
                        throw new opal\query\ValueException(
                            'Value for between operator cannot be a field reference'
                        );
                    }
                    
                    if($value instanceof core\IArrayProvider) {
                        $value = $value->toArray();
                    }
                    
                    if(!is_array($value) || count($value) < 2) {
                        throw new opal\query\ValueException(
                            'Value for between operator must be an array with 2 numeric elements'
                        );
                    }
                    
                    $temp = $value;
                    $value = array();
                    
                    for($i = 0; $i < 2; $i++) {
                        $part = array_shift($temp);
                        
                        if(!is_numeric($part)) {
                            throw new opal\query\ValueException(
                                'Value for between operator must be an array with 2 numeric elements'
                            );
                        }
                        
                        $value[] = $part;
                    }
                    
                    break;
            }
        }
        
        
        $this->_value = $value;
        $this->_preparedValue = null;
        $this->_hasPreparedValue = null;
        
        return $this;
    }

    protected function _flattenArray(array $array) {
        $output = array();

        foreach($array as $value) {
            if(is_array($value)) {
                $output = array_merge($output, $this->_flattenArray($value));
            } else {
                $output[] = $value;
            }
        }

        return $output;
    }
    
    public function getValue() {
        return $this->_value;
    }
    
    public function getPreparedValue() {
        if($this->_hasPreparedValue === false) {
            return $this->_value;
        }
        
        if(!$this->_hasPreparedValue) {
            $source = $this->_field->getSource();
            $adapter = $source->getAdapter();
            
            if($this->_value instanceof opal\query\ICorrelationQuery
            || $this->_value instanceof opal\query\IField
            || !$adapter instanceof opal\query\IIntegralAdapter) {
                $this->_hasPreparedValue = false;
                return $this->_value;
            }

            $this->_preparedValue = $this->_value;

            // Prepare
            switch($this->_operator) {
                case self::OP_IN:
                case self::OP_NOT_IN:
                    if(count($this->_value) > self::BETWEEN_CONVERSION_THRESHOLD) {
                        // TODO: convert to between set
                        //core\dump($this->_value);
                    }

                case self::OP_BETWEEN:
                case self::OP_NOT_BETWEEN:
                    $output = array();
                
                    foreach($this->_preparedValue as $part) {
                        $output[] = $this->_prepareInnerValue($adapter, $part);
                    }
                    
                    $this->_preparedValue = $output;
                    break;

                case self::OP_LIKE:
                case self::OP_NOT_LIKE:
                    $this->_preparedValue = (string)$this->_value;
                    break;
                    
                default:
                    $this->_preparedValue = $this->_prepareInnerValue(
                        $adapter, $this->_preparedValue
                    );
                    
                    break;
            }
            
            $this->_hasPreparedValue = true;
        }

        return $this->_preparedValue;
    }

    protected function _prepareInnerValue($adapter, $value) {
        $preparedValue = $adapter->prepareQueryClauseValue($this->_field, $value);

        if($preparedValue instanceof opal\record\IRecord) {
            $preparedValue = $preparedValue->getPrimaryManifest();
        }

        return $preparedValue;
    }
    
    public function referencesSourceAliases(array $sourceAliases) {
        if(in_array($this->_field->getSourceAlias(), $sourceAliases)) {
            return true;
        }
        
        if($this->_value instanceof opal\query\IField
        && in_array($this->_value->getSourceAlias(), $sourceAliases)) {
            return true;
        }
        
        return false;
    }
    
    public function getNonLocalFieldReferences() {
        $output = array();
        
        if($this->_value instanceof opal\query\IField
        && $this->_value->getSourceAlias() != $this->_field->getSourceAlias()) {
            $output[$this->_value->getQualifiedName()] = $this->_value;
        }
        
        return $output;
    }
    
// Dump
    public function getDumpProperties() {
        if($this->_isOr) {
            $type = 'OR';
        } else {
            $type = 'AND';
        }
        
        $field = $this->_field->getQualifiedName();
        
        if($this->_value instanceof opal\query\IField) {
            $value = $this->_value->getQualifiedName();
        } else if($this->_value instanceof opal\record\IRecord) {
            $value = $this->_value->getRecordAdapter()->getQuerySourceId().' : '.$this->_value->getPrimaryManifest();
        } else if($this->_value instanceof opal\query\IQuery) {
            $value = $this->_value;
        } else if($this->_value === null) {
            $value = 'NULL';
        } else if(is_bool($this->_value)) {
            $value = $this->_value ? 'TRUE' : 'FALSE';
        } else if(is_array($this->_value)) {
            $value = '(\''.implode('\', \'', $this->_value).'\')';
        } else {
            $value = '\''.(string)$this->_value.'\'';
        }
        
        if(is_string($value)) {
            return $type.' '.$field.' '.$this->_operator.' '.$value;
        } else {
            return [
                'clause' => $type.' '.$field.' '.$this->_operator, 
                'value' => $value
            ];
        }
    }
}
