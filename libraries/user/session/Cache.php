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

    public function insertDescriptor(IDescriptor $descriptor) {
        $id = 'd:'.$descriptor->getPublicKeyHex();

        $justStarted = $descriptor->justStarted;
        $descriptor->justStarted = false;

        $this->set($id, $descriptor);

        $descriptor->justStarted = $justStarted;
        return $descriptor;
    }

    public function fetchDescriptor($publicKey) {
        return $this->get('d:'.bin2hex($publicKey));
    }

    public function removeDescriptor(IDescriptor $descriptor) {
        $id = 'd:'.$descriptor->getPublicKeyHex();
        $this->remove($id);
    }


    public function fetchNode(IBucket $bucket, $key) {
        $id = 'i:'.$bucket->getDescriptor()->getIdHex().'/'.$bucket->getName().'#'.$key;
        return $this->get($id);
    }

    public function insertNode(IBucket $bucket, INode $node) {
        $id = 'i:'.$bucket->getDescriptor()->getIdHex().'/'.$bucket->getName().'#'.$node->key;
        $this->set($id, $node);
        return $node;
    }

    public function removeNode(IBucket $bucket, $key) {
        $id = 'i:'.$bucket->getDescriptor()->getIdHex().'/'.$bucket->getName().'#'.$key;
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
