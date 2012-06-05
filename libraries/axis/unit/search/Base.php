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

abstract class Base extends axis\Unit implements
    axis\IAdapterBasedStorageUnit {
    
    protected $_index;
    
    public static function loadAdapter(axis\IAdapterBasedStorageUnit $unit) {
        $config = axis\ConnectionConfig::getInstance($unit->getModel()->getApplication());
        $settings = $config->getSettingsFor($unit);
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
        
        $indexName = $unit->getApplication()->getUniquePrefix().'_'.preg_replace('/[^a-zA-Z0-9_]/', '_', $unit->getUnitId());
        $client = $class::factory($settings);
        
        return $client->getIndex($indexName);
    }
    
    public function __construct(axis\IModel $model, $unitName=null) {
        parent::__construct($model);
        $this->_index = self::loadAdapter($this);
    }
    
    public function getUnitType() {
        return 'search';
    }
    
    public function getUnitAdapter() {
        return $this->_index;
    }

    public function getIndex() {
        return $this->_index;
    }


    public function destroyStorage() {
        core\stub();
    }
    
    
    public function newDocument($id=null, array $values=null) {
        return $this->_index->newDocument($id, $values);
    }
    
    public function storeDocument(opal\search\IDocument $document) {
        $this->_index->storeDocument($document);
        return $this;
    }

    public function fetchByPrimary($keys) {
        core\stub($keys);
    }
}
