<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema\field;

use df\core;
use df\opal;

abstract class Base implements opal\rdbms\schema\IField, core\IDumpable {
    
    use opal\schema\TField;
    
    protected $_type;
    protected $_sqlVariant;
    protected $_nullConflictClause;
    protected $_collation;
    
    
    public static function factory(opal\rdbms\schema\ISchema $schema, $type, $name, array $args) {
        $type = strtolower($type);
        
        switch($type) {
            case 'bool':
            case 'boolean':
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':
                $classType = 'Int';
                break;
                
            case 'float':
            case 'double':
            case 'double precision':
            case 'real':
            case 'decimal':
                $classType = 'Float';
                break;
                
            case 'char':
            case 'varchar':
                $classType = 'Char';
                break;
                
            case 'tinytext':
            case 'smalltext':
            case 'text':
            case 'mediumtext':
            case 'longtext':
                $classType = 'Text';
                break;
                
            case 'tinyblob':
            case 'blob':
            case 'mediumblob':
            case 'longblob':
                $classType = 'Blob';
                break;
                
            case 'binary':
            case 'varbinary':
                $classType = 'Binary';
                break;
                
            case 'date':
            case 'datetime':
            case 'time':
            case 'year':
                $classType = 'DateTime';
                break;
                
            case 'timestamp':
                $classType = 'Timestamp';
                break;
                
            case 'enum':
            case 'set':
                $classType = 'Set';
                break;
                
            default:
                $classType = ucfirst($type);
                break;
        }
        
        $class = 'df\\opal\\rdbms\\schema\\field\\'.$classType;
        
        if(!class_exists($class)) {
            throw new opal\rdbms\UnexpectedValueException(
                'Field type '.$type.' is not currently supported'
            );
        }
        
        return new $class($schema, $type, $name, $args);
    }
    
    public function __construct(opal\rdbms\schema\ISchema $schema, $type, $name, array $args) {
        $this->_setName($name);
        $this->_sqlVariant = $schema->getSqlVariant();
        $this->_type = $type;
        
        if(method_exists($this, '_init')) {
            call_user_func_array(array($this, '_init'), $args);    
        }
    }
    
    public function getType() {
        return $this->_type;
    }
    
    public function getSqlVariant() {
        return $this->_sqlVariant;
    }
    
    
    public function setNullConflictClause($clause) {
        if(is_string($clause) && !is_numeric($clause)) {
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
        
        switch((int)$clause) {
            case opal\schema\ROLLBACK:
            case opal\schema\ABORT:
            case opal\schema\FAIL:
            case opal\schema\IGNORE:
            case opal\schema\REPLACE:
                break;
                
            default:
                $clause = null;
        }
        
        $this->_nullConflictClause = $clause;
        return $this;
    }

    public function getNullConflictClauseId() {
        return $this->_nullConflictClause;
    }
    
    public function getNullConflictClauseName() {
        switch($this->_nullConflictClause) {
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
    
    
    public function setCollation($collation) {
        if($collation != $this->_collation) {
            $this->_hasChanged = true;
        }
        
        $this->_collation = $collation;
        return $this;
    }
    
    public function getCollation() {
        return $this->_collation;
    }
    
    public function __toString() {
        try {
            return $this->toString();
        } catch(\Exception $e) {
            return $this->_name.' '.strtoupper($this->_type);
        }
    }
    
    public function getDumpProperties() {
        return $this->toString();
    }
}
