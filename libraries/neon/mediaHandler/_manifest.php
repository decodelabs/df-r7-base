<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\neon\mediaHandler;

use df\core;

interface IMediaHandler extends core\IManager
{
    public function getName(): string;
    public static function getDisplayName(): string;

    public function publishFile($fileId, $oldVersionId, $newVersionId, $filePath, $fileName);
    public function transferFile($fileId, $versionId, $isActive, $filePath, $fileName);
    public function activateVersion($fileId, $oldVersionId, $newVersionId);
    public function purgeVersion($fileId, $versionId, $isActive);
    public function deleteFile($fileId);

    public function getDownloadUrl($fileId);
    public function getEmbedUrl($fileId);
    public function getVersionDownloadUrl($fileId, $versionId, $isActive);
    public function getImageUrl($fileId, $transformation = null);
    public function getVersionImageUrl($fileId, $versionId, $isActive, $transformation = null);
    public function hashFile($fileId, $versionId, $isActive);

    public static function getDefaultConfig();
}

interface ILocalDataHandler extends IMediaHandler
{
    public function getFilePath($fileId, $versionId);
}
