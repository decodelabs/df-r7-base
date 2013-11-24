<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

class Insert implements IInsertQuery, core\IDumpable {
    
    use TQuery;
    use TQuery_LocalSource;
    use TQuery_DataInsert;

    protected $_ifNotExists = false;

    public function __construct(ISourceManager $sourceManager, ISource $source, $row) {
        $this->_sourceManager = $sourceManager;
        $this->_source = $source;
        
        $this->setRow($row);
    }
    
    public function getQueryType() {
        return IQueryTypes::INSERT;
    }

    public function ifNotExists($flag=null) {
        if($flag !== null) {
            $this->_ifNotExists = (bool)$flag;
            return $this;
        }

        return $this->_ifNotExists;
    }
    
    
    public function execute() {
        $this->_row = $this->_deflateInsertValues($this->_row);
        
        $output = $this->_sourceManager->executeQuery($this, function($adapter) {
            return $adapter->executeInsertQuery($this);
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
