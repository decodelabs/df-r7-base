<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class BatchReplace implements IBatchReplaceQuery, core\IDumpable {
    
    use TQuery;
    use TQuery_BatchDataInsert;
    
    public function __construct(ISourceManager $sourceManager, ISource $source, $rows) {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
        
        $this->addRows($rows);
    }
    
    public function getQueryType() {
        return IQueryTypes::BATCH_REPLACE;
    }
    
    
// Execute
    public function execute() {
        if(!empty($this->_rows)) {
            $adapter = $this->_source->getAdapter();
            $fields = array();
            
            if($adapter instanceof IIntegralAdapter) {
                $this->_rows = $adapter->deflateBatchReplaceValues($this->_rows, $fields);
            }
            
            if(!empty($fields)) {
                $this->_fields = array_fill_keys($fields, true);
            }
            
            
            try {
                $this->_inserted += $adapter->executeBatchReplaceQuery($this);
            } catch(\Exception $e) {
                if($this->_sourceManager->handleQueryException($this, $e)) {
                    $this->_inserted += $adapter->executeBatchReplaceQuery($this);
                } else {
                    throw $e;
                }
            }
        }
        
        $this->clearRows();
        return $this->_inserted;
    }
}
