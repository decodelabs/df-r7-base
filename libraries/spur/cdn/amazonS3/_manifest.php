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

class ApiException extends RuntimeException implements core\IDumpable {

    public $apiCode;
    public $xml;

    public function __construct($apiCode, $message, $httpCode=500, core\xml\ITree $xml=null) {
        $this->apiCode = $apiCode;
        $this->xml = $xml;
        parent::__construct($message, $httpCode);
    }

    public function getApiCode() {
        return $this->apiCode;
    }

    public function getDumpProperties() {
        return $this->xml;
    }
}



// Interfaces
interface IStorageClass {
    const STANDARD = 'STANDARD';
    const RRS = 'REDUCED_REDUNDANCY';
}

interface IAcl {
    const PRIVATE_READ_WRITE = 'private';
    const PUBLIC_READ = 'public-read';
    const PUBLIC_READ_WRITE = 'public-read-write';
    const AUTHENTICATED_READ = 'authenticated-read';
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

    public function createBucket($name, $acl=IAcl::PRIVATE_READ_WRITE, $location=null);
    public function deleteBucket($name);
    public function getBucketList();
    public function getBucketLocation($bucket);
    public function getBucketObjectList($bucket, $prefix=null, $limit=null, $marker=null);

    public function getObjectInfo($bucket, $path);
    public function newUpload($bucket, $path, core\io\IFilePointer $file);
    public function newCopy($fromBucket, $fromPath, $toBucket, $toPath);
    public function renameFile($bucket, $path, $newName, $acl=IAcl::PRIVATE_READ_WRITE);
    public function deleteFile($bucket, $path);
    public function deleteFolder($bucket, $path);

    public function callServer(link\http\IRequest $request);
    public function getBucketUrl($bucket, $path, &$resource=null);
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

    public function setHeaderOverrides(array $headers);
    public function setHeaderOverride($key, $value);
    public function removeHeaderOverride($key);
    public function getHeaderOverrides();
    public function clearHeaderOverrides();

    public function send();
}

interface ICopy extends IUpload {
    public function setFromBucket($bucket);
    public function getFromBucket();
    public function setFromFilePath($path);
    public function getFromFilePath();
}
