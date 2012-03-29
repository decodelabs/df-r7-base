<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema\constraint;

use df\core;
use df\opal;

class Index implements opal\rdbms\schema\IIndex, core\IDumpable {
    
    use opal\schema\TConstraint_Index;
    use opal\rdbms\schema\TSqlVariantAware;
    
    protected $_conflictClause;
    protected $_indexType;
    protected $_keyBlockSize;
    protected $_fulltextParser;
    
    public function __construct(opal\rdbms\schema\ISchema $schema, $name, $fields=null) {
        $this->_setName($name);
        $this->setFields($fields);
        $this->_sqlVariant = $schema->getSqlVariant();
    }
    
    public function setConflictClause($clause) {
        if(is_string($clause)) {
            switch(strtoupper($clause)) {
                case 'ROLLBACK':
                    $clause = opal\schema\ROLLBACK;
                    break;
                    
                case 'ABORT':
                    $clause = opal\schema\ABORT;
                    break;
                    
                case 'FAIL':
                    $clause = opal\schema\FAIL;
                    break;
                    
                case 'IGNORE':
                    $clause = opal\schema\IGNORE;
                    break;
                    
                case 'REPLACE':
                    $clause = opal\schema\REPLACE;
                    break;
                    
                default:
                    $clause = null;
            }
        }
        
        switch($clause) {
            case opal\schema\ROLLBACK:
            case opal\schema\ABORT:
            case opal\schema\FAIL:
            case opal\schema\IGNORE:
            case opal\schema\REPLACE:
                break;
                
            default:
                $clause = null;
        }
        
        $this->_conflictClause = $clause;
        return $this;
    }

    public function getConflictClause() {
        return $this->_conflictClause;
    }
    
    public function getConflictClauseName() {
        switch($this->_conflictClause) {
            case opal\schema\ROLLBACK:
                return 'ROLLBACK';
                
            case opal\schema\ABORT:
                return 'ABORT';
                
            case opal\schema\FAIL:
                return 'FAIL';
                
            case opal\schema\IGNORE:
                return 'IGNORE';
                
            case opal\schema\REPLACE:
                return 'REPLACE';
        }
    }
    
    public function setIndexType($type) {
        if($type !== $this->_indexType) {
            $this->_hasChanged = true;
        }
        
        $this->_indexType = $type;
        return $this;
    }
    
    public function getIndexType() {
        return $this->_indexType;
    }
    
    public function setKeyBlockSize($size) {
        if($size !== null) {
            $size = (int)$size;
        }
        
        if($size !== $this->_keyBlockSize) {
            $this->_hasChanged = true;
        }
        
        $this->_keyBlockSize = $size;
        return $this;
    }
    
    public function getKeyBlockSize() {
        return $this->_keyBlockSize;
    }
    
    public function setFulltextParser($parser) {
        if(strtoupper($this->_indexType) !== 'FULLTEXT') {
            $parser = null;
        }
        
        if($parser !== $this->_fulltextParser) {
            $this->_hasChanged = true;
        }
        
        $this->_fulltextParser = $parser;
        return $this;
    }
    
    public function getFulltextParser() {
        return $this->_fulltextParser;
    }
    
    
// Dump
    public function getDumpProperties() {
        $output = $this->_name;
        
        if($this->_isUnique) {
            $output .= ' UNIQUE';
        }
        
        $fields = array();
        
        foreach($this->_fieldReferences as $reference) {
            $str = $reference->getField()->getName();
            
            if(null !== ($size = $reference->getSize())) {
                $str .= '('.$size.')';
            }
            
            $str .= ' '.($reference->isDescending() ? 'DESC' : 'ASC');
            $fields[] = $str;
        }
        
        if($this->_indexType !== null) {
            $output .= ' USING '.$this->_indexType;
        }
        
        $output .= ' ('.implode(', ', $fields).')';
        
        if($this->_conflictClause !== null) {
            $output .= ' ON CONFLICT '.$this->getConflictClauseName();
        }
        
        $output .= ' ['.$this->_sqlVariant.']';
        
        if($this->isVoid()) {
            $output .= ' **VOID**';
        }
        
        return $output;
    }
}
