<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\rdbms\schema;

use df;
use df\core;
use df\opal;

// Interfaces
interface ISqlVariantAware {
    public function getSqlVariant();
}


trait TSqlVariantAware {
    
    protected $_sqlVariant;
    
    public function getSqlVariant() {
        return $this->_sqlVariant;
    }
}


interface ISchema extends 
    opal\schema\ISchema, 
    opal\schema\IFieldProvider, 
    opal\schema\IIndexProvider, 
    opal\schema\IForeignKeyProvider, 
    opal\schema\ITriggerProvider, 
    ISqlVariantAware,
    core\policy\IEntity {
    public function getAdapter();
    public function getTable();
    public function getSqlVariant();
    public function isTemporary($flag=null);
    public function normalize();
}


interface IMultiEngineSchema extends ISchema {
    public function setEngine($engine);
    public function getEngine();
}

interface IAutoIncrementableSchema extends ISchema {
    public function setAutoIncrementPosition($position);
    public function getAutoIncrementPosition();
}

interface ICharacterSetAwareSchema extends ISchema {
    public function setCharacterSet($charset);
    public function getCharacterSet();
}

interface ICollationAwareSchema extends ISchema {
    public function setCollation($collation);
    public function getCollation();
}

interface IKeyBlockSizeAwareSchema extends ISchema {
    public function setKeyBlockSize($size);
    public function getKeyBlockSize();
}






interface IField extends opal\schema\IField, ISqlVariantAware, core\IStringProvider, core\string\ICollationAware {
    public function setNullConflictClause($clause);
    public function getNullConflictClauseId();
    public function getNullConflictClauseName();
}



interface IIndex extends opal\schema\IIndex, ISqlVariantAware {
    public function setConflictClause($clause);
    public function getConflictClause();
    public function getConflictClauseName();
    public function setIndexType($type);
    public function getIndexType();
    public function setKeyBlockSize($size);
    public function getKeyBlockSize();
    public function setFulltextParser($parser);
    public function getFulltextParser();
}

interface IForeignKey extends opal\schema\IForeignKey, ISqlVariantAware {}
interface ITrigger extends opal\schema\ITrigger, ISqlVariantAware, core\string\ICharacterSetAware {}