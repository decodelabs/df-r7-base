<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\field;

use df;
use df\core;
use df\opal;

class Aggregate implements opal\query\IAggregateField, core\IDumpable {
    
    const TYPE_COUNT = 1;
    const TYPE_SUM = 2;
    const TYPE_AVG = 3;
    const TYPE_MIN = 4;
    const TYPE_MAX = 5;
    
    protected $_type;
    protected $_alias;
    protected $_targetField;
    protected $_source;
    
    public static function typeIdToName($id) {
        switch($id) {
            case self::TYPE_COUNT:
                return 'COUNT';
                
            case self::TYPE_SUM:
                return 'SUM';    
            
            case self::TYPE_AVG:
                return 'AVG';
                
            case self::TYPE_MIN:
                return 'MIN';
                
            case self::TYPE_MAX:
                return 'MAX';
        }
    }
    
    public function __construct(opal\query\ISource $source, $type, opal\query\IField $targetField, $alias) {
        $this->_source = $source;
            
        if(is_string($type)) {
            $type = strtoupper($type);
        }
        
        switch($type) {
            case self::TYPE_COUNT:
            case 'COUNT':
                $type = self::TYPE_COUNT;
                break;
                
            case self::TYPE_SUM:
            case 'SUM':
                $type = self::TYPE_SUM;
                break;
                
            case self::TYPE_AVG:
            case 'AVG':
                $type = self::TYPE_AVG;
                break;
                
            case self::TYPE_MIN:
            case 'MIN':
                $type = self::TYPE_MIN;
                break;
                
            case self::TYPE_MAX:
            case 'MAX':
                $type = self::TYPE_MAX;
                break;
                
            default:
                throw new opal\query\InvalidArgumentException(
                    'Aggregate function '.$type.' is not recognised'
                );
        }
            
        $this->_type = $type;
        $this->_targetField = $targetField;
        
        if($alias === null) {
            $alias = $this->getName();
        }
        
        $this->_alias = $alias;
    }

    public function getSource() {
        return $this->_source;
    }
    
    public function getSourceAlias() {
        return $this->_source->getAlias();
    }
    
    public function getType() {
        return $this->_type;
    }
    
    public function getTypeName() {
        return self::typeIdToName($this->_type);
    }
    
    public function getName() {
        return $this->getTypeName().'('.$this->_targetField->getName().')';
    }
    
    public function getQualifiedName() {
        return $this->getTypeName().'('.$this->_targetField->getQualifiedName().')';
    }
    
    public function getAlias() {
        return $this->_alias;
    }
    
    public function getTargetField() {
        return $this->_targetField;
    }
    
    public function hasDiscreetAlias() {
        return $this->_alias !== $this->getName();
    }
    
    public function dereference() {
        return array($this);
    }
    
// Dump
    public function getDumpProperties() {
        $output = $this->getQualifiedName();
        
        if($this->hasDiscreetAlias()) {
            $output .= ' as '.$this->getAlias();
        }

        return $output;
    }
}
