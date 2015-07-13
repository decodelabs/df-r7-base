<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\session;

use df;
use df\core;
use df\user;

class Cache extends core\cache\Base implements ICache {
    
    const CACHE_ID = 'session';
    
    public function insertDescriptor(IDescriptor $descriptor) {
        $id = 'd:'.$descriptor->getExternalIdHex();
        
        $justStarted = $descriptor->justStarted;
        $descriptor->justStarted = false;
        
        $this->set($id, $descriptor);
        
        $descriptor->justStarted = $justStarted;
        return $descriptor;
    }
    
    public function fetchDescriptor($externalId) {
        return $this->get('d:'.bin2hex($externalId));
    }
    
    public function removeDescriptor(IDescriptor $descriptor) {
        $id = 'd:'.$descriptor->getExternalIdHex();
        $this->remove($id);
    }
    
    
    public function fetchNode(IBucket $bucket, $key) {
        $id = 'i:'.$bucket->getDescriptor()->getInternalIdHex().'/'.$bucket->getName().'#'.$key;
        return $this->get($id);
    }
    
    public function insertNode(IBucket $bucket, INode $node) {
        $id = 'i:'.$bucket->getDescriptor()->getInternalIdHex().'/'.$bucket->getName().'#'.$node->key;
        
        $isLocked = $node->isLocked;
        $node->isLocked = false;
        
        $this->set($id, $node);
        
        $node->isLocked = $isLocked;
        return $node;
    }
    
    public function removeNode(IBucket $bucket, $key) {
        $id = 'i:'.$bucket->getDescriptor()->getInternalIdHex().'/'.$bucket->getName().'#'.$key;
        $this->remove($id);
    }


    public function setGlobalKeyringTimestamp() {
        $this->set('m:globalKeyringTimestamp', time());
    }

    public function shouldRegenerateKeyring($keyringTimestamp) {
        if(!$keyringTimestamp) {
            return true;
        }

        $timestamp = $this->get('m:globalKeyringTimestamp');
        $output = false;

        if($timestamp === null) {
            $this->setGlobalKeyringTimestamp();
            $output = true;
        } else {
            $output = $timestamp > $keyringTimestamp;
        }

        return $output;
    }
}
