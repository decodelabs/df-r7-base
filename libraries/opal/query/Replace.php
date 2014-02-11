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
    use TQuery_Locational;
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
        $this->_row = $this->_deflateInsertValues($this->_row);
        
        $output = $this->_sourceManager->executeQuery($this, function($adapter) {
            return $adapter->executeReplaceQuery($this);
        });

        return $this->_normalizeInsertId($output, $this->_row);
    }


// Dump
    public function getDumpProperties() {
        return array(
            'source' => $this->_source->getAdapter(),
            'row' => $this->_row
        );
    }
}
