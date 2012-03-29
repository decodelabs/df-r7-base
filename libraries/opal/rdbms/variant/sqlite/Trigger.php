<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\variant\sqlite;

use df;
use df\core;
use df\opal;

class Trigger extends opal\rdbms\schema\constraint\Trigger {
    
    protected $_isTemporary = false;
    protected $_updateFields = array();
    protected $_whenExpression;
    
    public function isTemporary($flag=null) {
        if($flag !== null) {
            $this->_isTemporary = (bool)$flag;
            return $this;
        }
        
        return $this->_isTemporary;
    }
    
    public function setUpdateFields(array $fields) {
        $this->_updateFields = $fields;
        return $this;
    }
    
    public function getUpdateFields() {
        return $this->_updateFields;
    }
    
    public function setWhenExpression($expression) {
        $this->_whenExpression = $expression;
        return $this;
    }
    
    public function getWhenExpression() {
        return $this->_whenExpression;
    }
    
    protected function _hasFieldReference(array $fields) {
        $regex = '/(OLD|NEW)[`]?\.[`]?('.implode('|', $fields).')[`]?/i';
        
        foreach($this->_statements as $statement) {
            if(preg_match($regex, $this->_statement)) {
                return true;
            }
        }
        
        return false;
    }
    
    
// Dump
    public function getDumpProperties() {
        $output = $this->_isTemporary ? 'TEMP ' : '';
        $output .= $this->_name;
        $output .= ' '.$this->getTimingName();
        $output .= ' '.$this->getEventName();
        
        if(!empty($this->_updateFields)) {
            $output .= ' OF '.implode(', ', $this->_updateFields);
        }
        
        if($this->_whenExpression !== null) {
            $output .= ' WHEN '.$this->_whenExpression;
        }
        
        $output .= ' '.implode('; ', $this->_statements);
        $output .= ' ['.$this->_sqlVariant.']';
        
        return $output;
    }
}
