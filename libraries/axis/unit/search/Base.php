<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\axis\unit\search;

use df;
use df\core;
use df\axis;
use df\opal;

abstract class Base implements axis\IAdapterBasedStorageUnit {
    
    use axis\TUnit;
    use axis\TAdapterBasedStorageUnit;

    public function __construct(axis\IModel $model, $unitName=null) {
        $this->_model = $model;
        $this->_loadAdapter();
    }
    
    protected function _loadAdapter() {
        $config = axis\ConnectionConfig::getInstance();
        $settings = $config->getSettingsFor($this);
        $adapterId = lcfirst($settings['adapter']);
        
        if(empty($adapterId)) {
            throw new axis\RuntimeException(
                'No adapter has been configured for search unit type'
            );
        }
        
        $class = 'df\\opal\\search\\'.$adapterId.'\\Client';
        
        if(!class_exists($class)) {
            throw new axis\RuntimeException(
                'Opal search index adapter '.$adapterId.' could not be found'
            );
        }
        
        $indexName = df\Launchpad::$application->getUniquePrefix().'_'.preg_replace('/[^a-zA-Z0-9_]/', '_', $this->getUnitId());
        $client = $class::factory($settings);
        
        $this->_adapter = $client->getIndex($indexName);
    }

    public function getUnitType() {
        return 'search';
    }
    

    public function getIndex() {
        return $this->_adapter;
    }
    
    public function getClient() {
        return $this->_adapter->getClient();
    }


    public function destroyStorage() {
        core\stub();
    }
    
    public function storageExists() {
        return core\stub();
    }

    public function getStorageBackendName() {
        core\stub($this);
    }
    
    
    public function newDocument($id=null, array $values=null) {
        return $this->_adapter->newDocument($id, $values);
    }
    
    public function storeDocument(opal\search\IDocument $document) {
        $this->_adapter->storeDocument($document);
        return $this;
    }
    
    public function storeDocumentList(array $documents) {
        $this->_adapter->storeDocumentList($documents);
        return $this;
    }
    
    public function find($query) {
        return $this->_adapter->find($query);
    }

    public function fetchByPrimary($keys) {
        core\stub($keys);
    }
}
