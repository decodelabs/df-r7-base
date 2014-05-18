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

class Upload implements IUpload {
    
    use core\TAttributeContainer;

    protected $_file;
    protected $_bucket;
    protected $_path;
    protected $_acl = IAcl::PRIVATE_READ_WRITE;
    protected $_storageClass = IStorageClass::STANDARD;
    protected $_encryption = IEncryption::NONE;
    protected $_headers = [];
    protected $_mediator;

    public function __construct(IMediator $mediator, $bucket, $targetFilePath, core\io\IFilePointer $file) {
        $this->_mediator = $mediator;
        $this->setBucket($bucket);
        $this->setTargetFilePath($targetFilePath);
        $this->setFile($file);
    }

    public function getMediator() {
        return $this->_mediator;
    }

    public function setBucket($bucket) {
        $this->_bucket = $bucket;
        return $this;
    }

    public function getBucket() {
        return $this->_bucket;
    }

    public function setTargetFilePath($path) {
        $this->_path = $path;
        return $this;
    }

    public function getTargetFilePath() {
        return $this->_path;
    }

    public function setFile(core\io\IFilePointer $file) {
        $this->_file = $file;
        return $this;
    }

    public function getFile() {
        return $this->_file;
    }

    public function setAcl($acl) {
        switch($acl) {
            case IAcl::PRIVATE_READ_WRITE:
            case IAcl::PUBLIC_READ:
            case IAcl::PUBLIC_READ_WRITE:
            case IAcl::AUTHENTICATED_READ:
                break;

            default:
                $acl = IAcl::PRIVATE_READ_WRITE;
                break;
        }

        $this->_acl = $acl;
        return $this;
    }

    public function getAcl() {
        return $this->_acl;
    }

    public function setStorageClass($class) {
        switch($class) {
            case IStorageClass::STANDARD:
            case IStorageClass::RRS:
                break;

            default:
                $class = IStorageClass::STANDARD;
                break;
        }

        $this->_storageClass = $class;
        return $this;
    }

    public function setEncryption($encryption) {
        switch($encryption) {
            case IEncryption::NONE:
            case IEncryption::AES256:
                break;

            default:
                $encryption = IEncryption::NONE;
                break;
        }

        $this->_encryption = $encryption;
        return $this;
    }

    public function getEncryption() {
        return $this->_encryption;
    }


// Headers
    public function setHeaderOverrides(array $headers) {
        $this->clearHeaderOverrides();

        foreach($headers as $key => $value) {
            $this->setHeaderOverride($key, $value);
        }

        return $this;
    }

    public function setHeaderOverride($key, $value) {
        $this->_headers[strtolower($key)] = $value;
        return $this;
    }

    public function removeHeaderOverride($key) {
        $key = strtolower($key);
        unset($this->_headers[$key]);
        return $this;
    }

    public function getHeaderOverrides() {
        return $this->_headers;
    }

    public function clearHeaderOverrides() {
        $this->_headers = [];
        return $this;
    }


    public function send() {
        $request = $this->_mediator->_newRequest('put', $this->_path, $this->_bucket);
        $request->setBodyData($this->_file);

        $headers = $request->getHeaders();
        $headers->set('x-amz-acl', $this->_acl);

        if($this->_storageClass !== IStorageClass::STANDARD) {
            $headers->set('x-amz-storage-class', $this->_storageClass);
        }

        if($this->_encryption !== IEncryption::NONE) {
            $headers->set('x-amz-server-side-encryption', $this->_encryption);
        }

        $headers->set('Content-MD5', base64_encode($this->_file->getRawHash('md5')));

        foreach($this->_attributes as $key => $value) {
            $headers->set('x-amz-meta-'.$key, $value);
        }

        foreach($this->_headers as $key => $value) {
            $headers->set($key, $value);
        }

        $response = $this->_mediator->callServer($request);
        return $this;
    }
}