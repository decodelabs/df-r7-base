<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\clause;

use df;
use df\core;
use df\opal;
    
class Matcher implements opal\query\IClauseMatcher {

    protected $_index = array();
    protected $_isFieldComparison = false;


    public function __construct(array $clauses, $isFieldComparison=false) {
        $this->_isFieldComparison = (bool)$isFieldComparison;

        $set = array();
        
        foreach($clauses as $clause) {
            if($clause->isOr() && !empty($set)) {
                $this->_index[] = $set;
                $set = array();
            }
            
            if($clause instanceof opal\query\IClauseList) {
                $clause = new self($clause->toArray(), $this->_isFieldComparison);
                
                if(empty($clause->_index)) {
                    continue;
                }
            } else {
                $clause = $this->_prepareClauseBundle($clause);
            }
            
            $set[] = $clause;
        }
        
        if(!empty($set)) {
            $this->_index[] = $set;
        }
    }

    protected function _prepareClauseBundle(opal\query\IClause $clause) {
        $output = new \stdClass();
        $output->clause = clone $clause;
        $output->operator = $clause->getOperator();

        $output->fieldQualifiedName = $clause->getField()->getQualifiedName();
        $value = $clause->getValue();

        if($value instanceof opal\query\IField) {
            $output->valueQualifiedName = $value->getQualifiedName();
        } else {
            $output->valueQualifiedName = null;
        }

        if(!$this->_isFieldComparison) {
            $output->compare = $clause->getPreparedValue();

            if($output->compare instanceof opal\query\IField) {
                core\stub('Field comparison', $output);
            }
            
            if($output->compare instanceof opal\query\ISelectQuery) {
                $source = $output->compare->getSource();
                
                if(null === ($targetField = $source->getFirstOutputDataField())) {
                    throw new opal\query\ValueException(
                        'Clause subquery does not have a distinct return field'
                    );
                }
                
                
                switch($clause->getOperator()) {
                    case opal\query\clause\Clause::OP_EQ:
                    case opal\query\clause\Clause::OP_NEQ:
                    case opal\query\clause\Clause::OP_LIKE:
                    case opal\query\clause\Clause::OP_NOT_LIKE:
                    case opal\query\clause\Clause::OP_CONTAINS:
                    case opal\query\clause\Clause::OP_NOT_CONTAINS:
                    case opal\query\clause\Clause::OP_BEGINS:
                    case opal\query\clause\Clause::OP_NOT_BEGINS:
                    case opal\query\clause\Clause::OP_ENDS:
                    case opal\query\clause\Clause::OP_NOT_ENDS:
                        $limit = $output->compare->getLimit();
                        $output->compare->limit(1);
                        $clause->setValue($output->compare->toValue($targetField->getName()));
                        $output->compare->limit($limit);
                        break;
                        
                    case opal\query\clause\Clause::OP_IN:
                    case opal\query\clause\Clause::OP_NOT_IN:
                        $clause->setValue($output->compare->toList($targetField->getName()));
                        break;
                        
                    case opal\query\clause\Clause::OP_GT:
                    case opal\query\clause\Clause::OP_GTE:
                        $source = $output->compare->getSource();

                        $source->addOutputField(
                            new opal\query\field\Aggregate(
                                $source, 'MAX', $targetField, 'max'
                            )
                        );
                        
                        $clause->setValue($output->compare->toValue('max'));
                        break;
                        
                    case opal\query\clause\Clause::OP_LT: 
                    case opal\query\clause\Clause::OP_LTE:
                        $source = $output->compare->getSource();

                        $source->addOutputField(
                            new opal\query\field\Aggregate(
                                $source, 'MIN', $targetField, 'min'
                            )
                        );
                        
                        $clause->setValue($output->compare->toValue('min'));
                        break;
                        
                        
                    case opal\query\clause\Clause::OP_BETWEEN:
                    case opal\query\clause\Clause::OP_NOT_BETWEEN:
                        throw new OperatorException(
                            'Operator '.$operator.' is not valid for clause subqueries'
                        );
                        
                    
                    default:
                        throw new opal\query\OperatorException(
                            'Operator '.$operator.' is not recognized'
                        );
                }

                $output->compare = $clause->getPreparedValue();
            }
        } else {
            $output->compare = null;
        }

        return $output;
    }


    public function testRow(array $row) {
        if(empty($this->_index)) {
            return true;
        }
        
        foreach($this->_index as $set) {
            $test = true;
            
            foreach($set as $bundle) {
                if($bundle instanceof opal\query\IClauseMatcher) {
                    $test &= $bundle->testRow($row);
                } else {
                    if(isset($row[$bundle->fieldQualifiedName])) {
                        $value = $row[$bundle->fieldQualifiedName];
                    } else {
                        $value = null;
                    }

                    $test &= $this->compare($value, $bundle->operator, $bundle->compare);
                }
            }
            
            if($test) {
                return true;
            }
        }
        
        return false;
    }
    
    public function testRowMatch(array $row, array $joinRow) {
        if(empty($this->_index)) {
            return true;
        }
        
        foreach($this->_index as $set) {
            $test = true;
            
            foreach($set as $bundle) {
                if($bundle instanceof opal\query\IClauseMatcher) {
                    $test &= $bundle->testRowMatch($row, $joinRow);
                } else {
                    $value = null;
                    $compare = null;

                    if(isset($joinRow[$bundle->fieldQualifiedName])) {
                        $value = $joinRow[$bundle->fieldQualifiedName];
                    }
                    
                    if(isset($row[$bundle->valueQualifiedName])) {
                        $compare = $row[$bundle->valueQualifiedName];
                    }

                    $test &= $this->compare($value, $bundle->operator, $compare);
                }
            }
            
            if($test) {
                return true;
            }
        }
        
        return false;
    }


    public static function compare($value, $operator, $compare) {
        switch($operator) {
            case opal\query\clause\Clause::OP_EQ:
                return $value === $compare;
                
            case opal\query\clause\Clause::OP_NEQ:
                return $value != $compare;
                
            case opal\query\clause\Clause::OP_GT:
                return $value > $compare;
                
            case opal\query\clause\Clause::OP_GTE:
                return $value >= $compare;
                
            case opal\query\clause\Clause::OP_LT: 
                return $value < $compare;
                
            case opal\query\clause\Clause::OP_LTE:
                return $value <= $compare;
            
            case opal\query\clause\Clause::OP_IN:
                return in_array($value, $compare);
                
            case opal\query\clause\Clause::OP_NOT_IN:
                return !in_array($value, $compare);
            
            case opal\query\clause\Clause::OP_BETWEEN:
                return $compare[0] <= $value && $value <= $compare[1];
                
            case opal\query\clause\Clause::OP_NOT_BETWEEN:
                return !($compare[0] <= $value && $value <= $compare[1]);
            
            case opal\query\clause\Clause::OP_LIKE:
                return core\string\Util::likeMatch($compare, $value);
                
            case opal\query\clause\Clause::OP_NOT_LIKE:
                return !core\string\Util::likeMatch($compare, $value);
            
            case opal\query\clause\Clause::OP_CONTAINS:
                return core\string\Util::contains($compare, $value);
                
            case opal\query\clause\Clause::OP_NOT_CONTAINS:
                return !core\string\Util::contains($compare, $value);
            
            case opal\query\clause\Clause::OP_BEGINS:
                return core\string\Util::begins($compare, $value);
                
            case opal\query\clause\Clause::OP_NOT_BEGINS:
                return !core\string\Util::begins($compare, $value);
            
            case opal\query\clause\Clause::OP_ENDS:
                return core\string\Util::ends($compare, $value);
                
            case opal\query\clause\Clause::OP_NOT_ENDS:
                return !core\string\Util::ends($compare, $value);
            
            
            default:
                throw new opal\query\OperatorException(
                    'Operator '.$operator.' is not recognized'
                );
        }
    }
}