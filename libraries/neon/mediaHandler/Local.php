<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\neon\mediaHandler;

use df;
use df\core;
use df\neon;

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;

class Local extends Base implements ILocalDataHandler
{
    public static function getDisplayName(): string
    {
        return 'Local file system';
    }

    public function publishFile($fileId, $oldVersionId, $newVersionId, $filePath, $fileName)
    {
        $destination = $this->getFilePath($fileId, $newVersionId);
        Atlas::copyFile($filePath, $destination);

        return $this;
    }

    public function activateVersion($fileId, $oldVersionId, $newVersionId)
    {
        // noop
        return $this;
    }

    public function getDownloadUrl($fileId)
    {
        $output = '/media/download?file='.$fileId;

        if (Genesis::$build->shouldCacheBust()) {
            $output .= '&cts='.Genesis::$build->getCacheBuster();
        }

        return $output;
    }

    public function getEmbedUrl($fileId)
    {
        return $this->getDownloadUrl($fileId).'&embed';
    }

    public function getVersionDownloadUrl($fileId, $versionId, $isActive)
    {
        $output = '/media/download?version='.$versionId;

        if (Genesis::$build->shouldCacheBust()) {
            $output .= '&cts='.Genesis::$build->getCacheBuster();
        }

        return $output;
    }

    public function getFilePath($fileId, $versionId)
    {
        $storageKey = $this->_getStorageKey($fileId);
        return df\Launchpad::$app->getSharedDataPath().'/media/'.$storageKey.'/'.$fileId.'/'.$versionId;
    }

    public function purgeVersion($fileId, $versionId, $isActive)
    {
        $path = $this->getFilePath($fileId, $versionId);
        Atlas::deleteFile($path);

        return $this;
    }

    public function deleteFile($fileId)
    {
        $storageKey = $this->_getStorageKey($fileId);
        $path = df\Launchpad::$app->getSharedDataPath().'/media/'.$storageKey.'/'.$fileId;
        $dir = Atlas::dir($path);
        $parent = $dir->getParent();
        $dir->delete();

        if ($parent->isEmpty()) {
            $parent->delete();
        }

        return $this;
    }

    public function hashFile($fileId, $versionId, $isActive)
    {
        $file = Atlas::file($this->getFilePath($fileId, $versionId));

        if (!$file->exists()) {
            return null;
        }

        return $file->getRawHash('crc32');
    }
}
