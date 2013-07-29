<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class BatchInsert implements IBatchInsertQuery, core\IDumpable {
    
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_BatchDataInsert;    

    protected $_ifNotExists = false;
    
    public function __construct(ISourceManager $sourceManager, ISource $source, $rows) {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
        
        $this->addRows($rows);
    }
    
    public function getQueryType() {
        return IQueryTypes::BATCH_INSERT;
    }


    public function ifNotExists($flag=null) {
        if($flag !== null) {
            $this->_ifNotExists = (bool)$flag;
            return $this;
        }

        return $this->_ifNotExists;
    }
    

// Execute
    public function execute() {
        if(!empty($this->_rows)) {
            $adapter = $this->_source->getAdapter();
            
            $fields = array();
            
            if($adapter instanceof IIntegralAdapter) {
                $this->_rows = $adapter->deflateBatchInsertValues($this->_rows, $fields);
            }
            
            if(!empty($fields)) {
                $this->_fields = array_fill_keys($fields, true);
            }

            $this->_inserted += $this->_sourceManager->executeQuery($this, function($adapter) {
                return (int)$adapter->executeBatchInsertQuery($this);
            });
        }
        
        $this->clearRows();
        return $this->_inserted;
    }
    
    
// Dump
    public function getDumpProperties() {
        return array(
            'source' => $this->_source->getAdapter(),
            'fields' => implode(', ', array_keys($this->_fields)),
            'pending' => count($this->_rows),
            'inserted' => $this->_inserted,
            'flushThreshold' => $this->_flushThreshold
        );
    }
}
