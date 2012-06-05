<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\search\elastic;

use df;
use df\core;
use df\opal;

class Index implements opal\search\IIndex {
    
    protected $_name;
    protected $_client;
    
    public function __construct(Client $client, $name) {
        $this->_client = $client;
        $this->_name = $name;
    }
    
    
    public function newDocument($id=null, array $values=null) {
        return new Document($id, $values);
    }
    
    public function storeDocument(opal\search\IDocument $document) {
        core\stub($document);
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
}
