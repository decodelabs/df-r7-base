<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\mediaHandler;

use df;
use df\core;
use df\neon;

// Exceptions
interface IException {}
class RuntimeException extends \RuntimeException implements IException {}


// Interfaces    
interface IMediaHandler extends core\IManager {
    public function getName();
    public static function getDisplayName();

    public function publishFile($fileId, $oldVersionId, $newVersionId, $filePath, $fileName);
    public function transferFile($fileId, $versionId, $isActive, $filePath, $fileName);
    public function activateVersion($fileId, $oldVersionId, $newVersionId);
    public function purgeVersion($fileId, $versionId, $isActive);
    public function deleteFile($fileId);

    public function getDownloadUrl($fileId);
    public function getVersionDownloadUrl($fileId, $versionId, $isActive);
    public function getImageUrl($fileId, $transformation=null);
    public function getVersionImageUrl($fileId, $versionId, $isActive, $transformation=null);
    public function hashFile($fileId, $versionId, $isActive);

    public static function getDefaultConfig();
}

interface ILocalDataHandler extends IMediaHandler {
    public function getFilePath($fileId, $versionId);
}

interface IConfig extends core\IConfig {
    public function setDefaultHandler($handler);
    public function getDefaultHandler();

    public function setSettingsFor($handler, array $settings);
    public function getSettingsFor($handler);

    public function getEnabledHandlers();
}