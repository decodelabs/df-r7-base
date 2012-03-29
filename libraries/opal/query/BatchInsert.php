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
    use TQuery_BatchDataInsert;    
    
    public function __construct(ISourceManager $sourceManager, ISource $source, $rows) {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
        
        $this->addRows($rows);
    }
    
    public function getQueryType() {
        return IQueryTypes::BATCH_INSERT;
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
            
            try {
                $this->_inserted += $adapter->executeBatchInsertQuery($this);
            } catch(\Exception $e) {
                if($this->_sourceManager->handleQueryException($this, $e)) {
                    $this->_inserted += $adapter->executeBatchInsertQuery($this);
                } else {
                    throw $e;
                }
            }
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
