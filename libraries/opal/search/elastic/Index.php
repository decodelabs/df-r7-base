<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\search\elastic;

use df;
use df\core;
use df\opal;
use df\halo;

class Index implements opal\search\IIndex {
    
    protected $_name;
    protected $_client;
    
    public function __construct(Client $client, $name) {
        $this->_client = $client;
        $this->_name = $name;
    }
    
    
    public function getName() {
        return $this->_name;
    }
    
    public function getClient() {
        return $this->_client;
    }
    
    
    public function newDocument($id=null, array $values=null) {
        return new Document($id, $values);
    }
    
    public function storeDocument(opal\search\IDocument $document) {
        // TODO: convert to simple POST request
        $result = $this->_client->sendBulkRequest([
            ['index' => [
                '_index' => $this->_name,
                '_type' => 'test',
                '_id' => $document->getId()
            ]],
            $document->getPreparedValues()
        ]);
        
        return $this;
    }
    
    public function storeDocumentList(array $documents) {
        $dataList = [];
        
        foreach($documents as $document) {
            if(!$document instanceof opal\search\IDocument) {
                throw new InvalidArgumentException(
                    'Invalid document in document list'
                );
            }
            
            $dataList[] = ['index' => [
                '_index' => $this->_name,
                '_type' => 'test',
                '_id' => $document->getId()
            ]];
            
            $dataList[] = $document->getPreparedValues();
        }
        
        $result = $this->_client->sendBulkRequest($dataList);
        return $this;
    }
    
    public function deleteDocument($id) {
        if($id instanceof opal\search\IDocument) {
            $id = $id->getId();
        }
        
        core\stub($id);
    }
    
    public function hasDocument($id) {
        if($id instanceof opal\search\IDocument) {
            $id = $id->getId();
        }
        
        core\stub($id);
    }
    
    public function count() {
        core\stub();
    }
    
    
    public function find($query) {
        $uri = core\uri\Url::factory('get://'.$this->_name.'/_search');
        $uri->query->q = $query;
        
        $result = $this->_client->sendRequest($uri);
        core\dump($result);
    }
}
