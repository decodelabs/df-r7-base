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

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}

class ApiException extends RuntimeException {

    public $apiCode;

    public function __construct($apiCode, $message, $httpCode=500) {
        $this->apiCode = $apiCode;
        parent::__construct($message, $httpCode);
    }

    public function getApiCode() {
        return $this->apiCode;
    }
}



// Interfaces
interface IStorageClass {
    const STANDARD = 'STANDARD';
    const RRS = 'REDUCED_REDUNDANCY';
}

interface IAcl {
    const PRIVATE_RW = 'private';
    const PUBLIC_R = 'public-read';
    const PUBLIC_RW = 'public-read-write';
    const AUTHENTICATED_R = 'authenticated-read';
}

interface IEncryption {
    const NONE = null;
    const AES256 = 'AES256';
}


interface IMediator {
    public function getHttpClient();
    public function setAccessKey($key);
    public function getAccessKey();
    public function setSecretKey($key);
    public function getSecretKey();
    public function shouldUseSsl($flag=null);

    public function createBucket($name, $acl=IMediator::ACL_PRIVATE, $location=null);
    public function deleteBucket($name);
    public function getBucketList();
    public function getBucketLocation($bucket);
    public function getBucketObjectList($bucket, $limit=null, $marker=null);

    public function getObjectInfo($bucket, $path);

    public function callServer(link\http\IRequest $request);
}

interface IUpload extends core\IAttributeContainer {
    public function getMediator();
    public function setBucket($bucket);
    public function getBucket();
    public function setTargetFilePath($path);
    public function getTargetFilePath();
    public function setFile(core\io\IFilePointer $file);
    public function getFile();
    
    public function setAcl($acl);
    public function getAcl();
    public function setStorageClass($class);
    public function setEncryption($encryption);
    public function getEncryption();

    public function send();
}
