<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\cdn\amazonS3;

use df;
use df\core;
use df\spur;
use df\link;

class Copy extends Upload implements ICopy {
    
    protected $_fromBucket;
    protected $_fromPath;

    public function __construct(IMediator $mediator, $fromBucket, $fromPath, $toBucket, $toPath) {
        $this->_mediator = $mediator;
        $this->setBucket($toBucket);
        $this->setTargetFilePath($toPath);
        $this->setFromBucket($fromBucket);
        $this->setFromFilePath($fromPath);
    }

    public function setFromBucket($bucket) {
        $this->_fromBucket = $bucket;
        return $this;
    }

    public function getFromBucket() {
        return $this->_fromBucket;
    }

    public function setFromFilePath($path) {
        $this->_fromPath = $path;
        return $this;
    }

    public function getFromFilePath() {
        return $this->_fromPath;
    }

    public function send() {
        $request = $this->_mediator->_newRequest('put', $this->_path, $this->_bucket);

        $headers = $request->getHeaders();
        $headers->set('x-amz-acl', $this->_acl);

        if($this->_storageClass !== IStorageClass::STANDARD) {
            $headers->set('x-amz-storage-class', $this->_storageClass);
        }

        if($this->_encryption !== IEncryption::NONE) {
            $headers->set('x-amz-server-side-encryption', $this->_encryption);
        }

        foreach($this->_attributes as $key => $value) {
            $headers->set('x-amz-meta-'.$key, $value);
        }

        foreach($this->_headers as $key => $value) {
            $headers->set($key, $value);
        }

        $headers->set('x-amz-copy-source', sprintf('/%s/%s', $this->_fromBucket, rawurlencode($this->_fromPath)));
        //$headers->set('x-amz-metadata-directive', 'REPLACE');

        $response = $this->_mediator->callServer($request);
        return $this;
    }
}