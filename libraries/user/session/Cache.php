<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\user\session;

use df;
use df\core;
use df\user;

class Cache extends core\cache\Base {
    
    const CACHE_ID = 'session';
    
    public function insertDescriptor(user\session\IDescriptor $descriptor) {
        $key = 'd:'.$descriptor->getExternalIdHex();
        
        $justStarted = $descriptor->justStarted;
        $descriptor->justStarted = false;
        
        $this->set($key, $descriptor);
        
        $descriptor->justStarted = $justStarted;
        return $descriptor;
    }
    
    public function fetchDescriptor($externalId) {
        return $this->get('d:'.bin2hex($externalId));
    }
    
    public function removeDescriptor(user\session\IDescriptor $descriptor) {
        $key = 'd:'.$descriptor->getExternalIdHex();
        $this->remove($key);
    }
    
    
    public function fetchNode(user\session\IDescriptor $descriptor, $namespace, $nsKey) {
        $key = 'i:'.$descriptor->getInternalIdHex().'/'.$namespace.'#'.$nsKey;
        return $this->get($key);
    }
    
    public function insertNode(user\session\IDescriptor $descriptor, \stdClass $node) {
        $key = 'i:'.$descriptor->getInternalIdHex().'/'.$node->namespace.'#'.$node->key;
        
        $isLocked = $node->isLocked;
        $node->isLocked = false;
        
        $this->set($key, $node);
        
        $node->isLocked = $isLocked;
        return $node;
    }
    
    public function removeNode(user\session\IDescriptor $descriptor, $namespace, $nsKey) {
        $key = 'i:'.$descriptor->getInternalIdHex().'/'.$namespace.'#'.$nsKey;
        $this->remove($key);
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
