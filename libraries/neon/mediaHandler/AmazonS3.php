<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\mediaHandler;

use df;
use df\core;
use df\neon;
use df\spur;
use df\link;

class AmazonS3 extends Base {

    protected $_bucket;
    protected $_path;
    protected $_mediator;

    protected function __construct() {
        parent::__construct();

        $settings = $this->_getSettings();
        $accessKey = $settings['accessKey'];
        $secretKey = $settings['secretKey'];

        if(!$accessKey || !$secretKey) {
            throw new RuntimeException('S3 config does not contain valid access key and secret key');
        }

        $this->_mediator = new spur\cdn\amazonS3\Mediator($accessKey, $secretKey);
        $this->_bucket = $settings['bucket'];
        $this->_path = trim($settings['path'], '/');

        if(empty($this->_path)) {
            $this->_path = null;
        }
    }

    public static function getDefaultConfig() {
        return [
            'accessKey' => null,
            'secretKey' => null,
            'bucket' => null,
            'path' => null
        ];
    }

    public static function getDisplayName(): string {
        return 'Amazon S3';
    }

    public function publishFile($fileId, $oldVersionId, $newVersionId, $filePath, $fileName) {
        $basePath = $this->_path.'/media/'.$fileId;

        if($oldVersionId !== null) {
            $this->_mediator->moveFile(
                $this->_bucket,
                $basePath,
                $basePath.'/'.$oldVersionId,
                spur\cdn\amazonS3\IAcl::PUBLIC_READ
            );
        }

        $this->_mediator->newUpload($this->_bucket, $basePath, new core\fs\File($filePath, core\fs\Mode::READ_ONLY))
            ->setAcl(spur\cdn\amazonS3\IAcl::PUBLIC_READ)
            ->setAttributes(['crc32' => base64_encode(hash_file('crc32', $filePath, true))])
            ->setHeaderOverride('content-disposition', 'attachment; filename='.$fileName)
            ->send();

        return $this;
    }

    public function transferFile($fileId, $versionId, $isActive, $filePath, $fileName) {
        $this->_mediator->newUpload($this->_bucket, $this->_getVersionPath($fileId, $versionId, $isActive), new core\fs\File($filePath, core\fs\Mode::READ_ONLY))
            ->setAcl(spur\cdn\amazonS3\IAcl::PUBLIC_READ)
            ->setAttributes(['crc32' => base64_encode(hash_file('crc32', $filePath, true))])
            ->setHeaderOverride('content-disposition', 'attachment; filename='.$fileName)
            ->send();

        return $this;
    }

    public function activateVersion($fileId, $oldVersionId, $newVersionId) {
        $basePath = $this->_path.'/media/'.$fileId;

        $this->_mediator->moveFile(
            $this->_bucket,
            $basePath,
            $basePath.'/'.$oldVersionId,
            spur\cdn\amazonS3\IAcl::PUBLIC_READ
        );

        $this->_mediator->moveFile(
            $this->_bucket,
            $basePath.'/'.$newVersionId,
            $basePath,
            spur\cdn\amazonS3\IAcl::PUBLIC_READ
        );

        return $this;
    }

    public function getDownloadUrl($fileId) {
        return $this->_mediator->getBucketUrl($this->_bucket, $this->_path.'/media/'.$fileId);
    }

    public function getVersionDownloadUrl($fileId, $versionId, $isActive) {
        return $this->_mediator->getBucketUrl(
            $this->_bucket,
            $this->_getVersionPath($fileId, $versionId, $isActive)
        );
    }

    public function purgeVersion($fileId, $versionId, $isActive) {
        $this->_mediator->deleteFile(
            $this->_bucket,
            $this->_getVersionPath($fileId, $versionId, $isActive)
        );

        return $this;
    }

    public function deleteFile($fileId) {
        if(empty($fileId)) {
            // deleting the root folder would be bad!
            return;
        }

        $this->_mediator->deleteFolder($this->_bucket, $this->_path.'/media/'.$fileId);
        $this->_mediator->deleteFile($this->_bucket, $this->_path.'/media/'.$fileId);
        return $this;
    }

    public function hashFile($fileId, $versionId, $isActive) {
        $info = $this->_mediator->getObjectInfo(
            $this->_bucket,
            $this->_getVersionPath($fileId, $versionId, $isActive)
        );

        return @base64_decode($info['meta']['crc32']);
    }

    protected function _getVersionPath($fileId, $versionId, $isActive) {
        $path = $this->_path.'/media/'.$fileId;

        if(!$isActive) {
            $path .= '/'.$versionId;
        }

        return $path;
    }
}
