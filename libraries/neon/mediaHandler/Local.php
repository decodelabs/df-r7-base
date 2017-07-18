<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\mediaHandler;

use df;
use df\core;
use df\neon;

class Local extends Base implements ILocalDataHandler {

    public static function getDisplayName(): string {
        return 'Local file system';
    }

    public function publishFile($fileId, $oldVersionId, $newVersionId, $filePath, $fileName) {
        $destination = $this->getFilePath($fileId, $newVersionId);
        core\fs\File::copy($filePath, $destination);

        return $this;
    }

    public function activateVersion($fileId, $oldVersionId, $newVersionId) {
        // noop
        return $this;
    }

    public function getDownloadUrl($fileId) {
        $output = '/media/download?file='.$fileId;

        if(df\Launchpad::$compileTimestamp) {
            $output .= '&cts='.df\Launchpad::$compileTimestamp;
        }

        return $output;
    }

    public function getEmbedUrl($fileId) {
        return $this->getDownloadUrl($fileId).'&embed';
    }

    public function getVersionDownloadUrl($fileId, $versionId, $isActive) {
        $output = '/media/download?version='.$versionId;

        if(df\Launchpad::$compileTimestamp) {
            $output .= '&cts='.df\Launchpad::$compileTimestamp;
        }

        return $output;
    }

    public function getFilePath($fileId, $versionId) {
        $storageKey = $this->_getStorageKey($fileId);
        return df\Launchpad::$app->getSharedDataPath().'/media/'.$storageKey.'/'.$fileId.'/'.$versionId;
    }

    public function purgeVersion($fileId, $versionId, $isActive) {
        $path = $this->getFilePath($fileId, $versionId);
        core\fs\File::delete($path);

        return $this;
    }

    public function deleteFile($fileId) {
        $storageKey = $this->_getStorageKey($fileId);
        $path = df\Launchpad::$app->getSharedDataPath().'/media/'.$storageKey.'/'.$fileId;
        $dir = (new core\fs\Dir($path))->unlink();

        $parent = $dir->getParent();

        if($parent->isEmpty()) {
            $parent->unlink();
        }

        return $this;
    }

    public function hashFile($fileId, $versionId, $isActive) {
        $file = new core\fs\File($this->getFilePath($fileId, $versionId));

        if(!$file->exists()) {
            return null;
        }

        return $file->getRawHash('crc32');
    }
}
