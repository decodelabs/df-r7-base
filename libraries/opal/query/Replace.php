<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Replace implements IReplaceQuery, core\IDumpable {
    
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_DataInsert;
    
    public function __construct(ISourceManager $sourceManager, ISource $source, $row) {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
        
        $this->setRow($row);
    }
    
    public function getQueryType() {
        return IQueryTypes::REPLACE;
    }
    
    public function execute() {
        $adapter = $this->_source->getAdapter();
        
        if($adapter instanceof IIntegralAdapter) {
            $this->_row = $adapter->deflateReplaceValues($this->_row);
        }
        
        try {
            $output = $adapter->executeReplaceQuery($this);
        } catch(\Exception $e) {
            if($this->_sourceManager->handleQueryException($this, $e)) {
                $output = $adapter->executeReplaceQuery($this);
            } else {
                throw $e;
            }
        }
        
        if($adapter instanceof IIntegralAdapter) {
            $output = $adapter->normalizeReplaceId($output, $this->_row);
        }
        
        return $output;
    }


// Dump
    public function getDumpProperties() {
        return array(
            'source' => $this->_source->getAdapter(),
            'row' => $this->_row
        );
    }
}
