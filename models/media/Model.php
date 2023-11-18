<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\media;

use DecodeLabs\Dictum;
use DecodeLabs\Disciple;
use DecodeLabs\Exceptional;
use DecodeLabs\Guidance;
use DecodeLabs\Guidance\Uuid;
use DecodeLabs\Typify;
use df\apex;
use df\axis;
use df\core;
use df\neon;
use df\opal;

class Model extends axis\Model
{
    // IO
    public function publishFile($filePath, $bucket, $fileData = null, $publishIfMissing = false)
    {
        $isMissing = false;

        if (!is_file($filePath)) {
            if ($publishIfMissing) {
                $isMissing = true;
            } else {
                throw Exceptional::Runtime(
                    'Media file to publish could not be found'
                );
            }
        }

        if (!$bucket instanceof apex\models\media\bucket\Record) {
            $bucket = $this->bucket->ensureSlugExists((string)$bucket);
        }

        $fileData = core\collection\Tree::factory($fileData);

        $mediaHandler = $this->getMediaHandler();
        $bucketHandler = $bucket->getHandler();
        $onePerUser = $bucketHandler->allowOnePerUser();
        $isUserSpecific = $bucketHandler->isUserSpecific();
        $owner = $fileData->get('owner');

        $hash = $isMissing ? $fileData->get('hash') : hash_file('crc32', $filePath, true);
        $fileSize = $isMissing ? $fileData->get('fileSize', 0) : filesize($filePath);

        if ($isMissing || $onePerUser) {
            $file = null;
        } else {
            $file = $this->file->fetch()
                ->where('bucket', '=', $bucket)
                ->whereCorrelation('activeVersion', 'in', 'id')
                    ->from($this->version, 'version')
                    ->where('version.hash', '=', $hash)
                    ->where('version.fileSize', '=', $fileSize)
                    ->where('version.purgeDate', '=', null)
                    ->chainIf($isUserSpecific && $owner !== null, function ($clause) use ($owner) {
                        $clause->where('version.owner', '=', $owner);
                    })
                    ->endCorrelation()
                ->toRow();
        }

        if ($file) {
            if ($mediaHandler instanceof neon\mediaHandler\ILocalDataHandler) {
                $storeFilePath = $mediaHandler->getFilePath($file['id'], $file['#activeVersion']);

                if (!is_file($storeFilePath)) {
                    $mediaHandler->publishFile(
                        $file['id'],
                        null,
                        $file['#activeVersion'],
                        $filePath,
                        $file['fileName']
                    );
                }
            }

            return $file;
        }

        $file = null;

        if ($onePerUser) {
            $owner = $fileData->get('owner', Disciple::getId());

            if ($owner !== null) {
                $file = $this->file->fetch()
                    ->where('owner', '=', $owner)
                    ->where('bucket', '=', $bucket['id'])
                    ->toRow();
            }
        }

        $fileData = $this->_normalizeFileData(
            $filePath,
            $fileData,
            $bucket['id'],
            $file ? $file['id'] : null
        );

        if ($file) {
            $file->import([
                'fileName' => $fileData['fileName']
            ]);
        } else {
            $file = $this->file->newRecord([
                'bucket' => $bucket,
                'owner' => $fileData['owner'],
                'fileName' => $fileData['fileName'],
                'creationDate' => $fileData['creationDate']
            ]);
        }

        $version = $this->version->newRecord([
            'file' => $file,
            'isActive' => true,
            'owner' => $fileData->get('versionOwner', $fileData['owner']),
            'fileName' => $fileData['fileName'],
            'fileSize' => $fileData->get('fileSize', $fileSize),
            'contentType' => $fileData['contentType'],
            'notes' => $fileData['notes'],
            'hash' => $hash,
            'creationDate' => $fileData->get('versionCreationDate', 'now')
        ]);

        $oldVersionId = $file['#activeVersion'];
        $file->activeVersion = $version;
        $file->save();

        if (!$isMissing) {
            $mediaHandler->publishFile(
                $file['id'],
                $oldVersionId,
                $version['id'],
                $filePath,
                $file['fileName']
            );
        }

        return $file;
    }

    public function publishVersion(apex\models\media\file\Record $file, $filePath, $fileData = null, $publishIfMissing = false)
    {
        $isMissing = false;

        if (!is_file($filePath)) {
            if ($publishIfMissing) {
                $isMissing = true;
            } else {
                throw Exceptional::Runtime(
                    'Media file to publish could not be found'
                );
            }
        }

        $fileData = $this->_normalizeFileData(
            $filePath,
            $fileData,
            $file['#bucket'],
            $file['id']
        );

        $hash = $isMissing ? $fileData->get('hash') : hash_file('crc32', $filePath, true);
        $fileSize = $isMissing ? $fileData->get('fileSize', 0) : filesize($filePath);

        if ($isMissing) {
            $version = null;
        } else {
            $version = $this->version->fetch()
                ->where('file', '=', $file['id'])
                ->where('hash', '=', $hash)
                ->where('fileSize', '=', $fileSize)
                ->where('purgeDate', '=', null)
                ->toRow();
        }

        if ($version) {
            $version->import([
                'fileName' => $fileData['fileName'],
                'contentType' => $fileData['contentType']
            ]);

            if ($fileData->has('notes')) {
                $version->notes = $fileData['notes'];
            }
        } else {
            $version = $this->version->newRecord([
                'file' => $file,
                'owner' => $fileData['owner'],
                'fileName' => $fileData['fileName'],
                'fileSize' => $fileSize,
                'contentType' => $fileData['contentType'],
                'notes' => $fileData['notes'],
                'hash' => $hash,
                'creationDate' => $fileData['creationDate']
            ]);
        }

        $oldVersionId = $file['#activeVersion'];

        $file->import([
            'fileName' => $fileData['fileName'],
            'activeVersion' => $version
        ]);

        $this->version->update(['isActive' => false])
            ->where('file', '=', $file['id'])
            ->execute();

        $version->import([
            'file' => $file,
            'isActive' => true
        ]);

        $file->save();
        $version->save();

        if (!$isMissing) {
            $this->getMediaHandler()->publishFile(
                $file['id'],
                $oldVersionId,
                $version['id'],
                $filePath,
                $file['fileName']
            );
        }

        return $version;
    }

    public function activateVersion(apex\models\media\file\Record $file, apex\models\media\version\Record $version, $fileData = null)
    {
        if ($version['#file'] != $file['id']) {
            throw Exceptional::Runtime(
                'Version is not for selected file'
            );
        }

        $fileData = $this->_normalizeFileData(
            $version['fileName'],
            $fileData,
            $file['#bucket'],
            $file['id']
        );

        $oldVersionId = $file['#activeVersion'];

        $file->import([
            'fileName' => $fileData['fileName'],
            'activeVersion' => $version
        ]);

        $this->version->update(['isActive' => false])
            ->where('file', '=', $file['id'])
            ->execute();

        $version->import([
            'file' => $file,
            'isActive' => true
        ]);

        $file->save();

        $this->getMediaHandler()->activateVersion(
            $file['id'],
            $oldVersionId,
            $version['id']
        );

        return $this;
    }

    public function deleteFile(apex\models\media\file\Record $file)
    {
        $this->getMediaHandler()->deleteFile($file['id']);
        $this->version->delete()->where('file', '=', $file['id'])->execute();
        $file->delete();
        return $this;
    }

    public function purgeVersion(apex\models\media\version\Record $version)
    {
        $version->purgeDate = 'now';
        $version->save();

        $this->getMediaHandler()->purgeVersion(
            $version['#file'],
            $version['id'],
            $version['file'] && (string)$version['id'] == $version['file']['#activeVersion']
        );

        return $this;
    }


    protected function _normalizeFileData($filePath, $fileData, $bucketId, $fileId)
    {
        $fileData = core\collection\Tree::factory($fileData);

        if (!$fileData->has('owner') && Disciple::isLoggedIn()) {
            $fileData->owner = Disciple::getId();
        }

        if (!$fileData->has('fileName')) {
            $fileData->fileName = basename($filePath);
        }

        if (!$fileData->has('contentType')) {
            $fileData->contentType = Typify::detect($filePath);
        }

        if (!$fileData->has('creationDate')) {
            $fileData->creationDate = 'now';
        }

        return $fileData;
    }



    // Handler
    public function getMediaHandler()
    {
        return neon\mediaHandler\Base::getInstance();
    }

    public function isLocalDataMediaHandler()
    {
        $mediaHandler = $this->getMediaHandler();
        return $mediaHandler instanceof neon\mediaHandler\ILocalDataHandler;
    }

    public function getDownloadUrl($fileId)
    {
        return $this->getMediaHandler()->getDownloadUrl($this->_normalizeId($fileId));
    }

    public function getEmbedUrl($fileId)
    {
        return $this->getMediaHandler()->getEmbedUrl($this->_normalizeId($fileId));
    }

    public function getVersionDownloadUrl($fileId, $versionId, $isActive)
    {
        return $this->getMediaHandler()->getVersionDownloadUrl($this->_normalizeId($fileId), $this->_normalizeId($versionId), $isActive);
    }

    public function getImageUrl($fileId, $transformation = null)
    {
        return $this->getMediaHandler()->getImageUrl($this->_normalizeId($fileId), $transformation);
    }

    public function getVersionImageUrl($fileId, $versionId, $isActive, $transformation = null)
    {
        return $this->getMediaHandler()->getVersionImageUrl($this->_normalizeId($fileId), $this->_normalizeId($versionId), $isActive, $transformation);
    }

    protected function _normalizeId($id): ?string
    {
        if (
            (is_array($id) ||
            $id instanceof opal\record\IRecord) &&
            isset($id['id'])
        ) {
            $id = $id['id'];
        }

        if ($id === null) {
            return null;
        }

        if ($id instanceof Uuid) {
            return (string)$id;
        }

        if (!is_string($id)) {
            $id = (string)$id;
        }

        if (strlen($id) != 36) {
            $id = (string)Guidance::from($id);
        }

        return $id;
    }


    // Fetcher
    public function fetchActiveVersionForDownload($fileId)
    {
        $fileId = $this->normalizeFileId($fileId, $transformation);

        $output = $this->file->select('id as fileId', 'fileName')
            ->leftJoinRelation('activeVersion', 'id', 'contentType', 'creationDate')
            ->where('fileId', '=', $fileId)
            ->toRow();

        if (!$output) {
            throw Exceptional::NotFound(
                'File version for ' . $fileId . ' could not be found',
                ['http' => 404]
            );
        }

        $output['isActive'] = true;
        $output['transformation'] = $transformation;
        return $output;
    }

    public function fetchVersionForDownload($versionId)
    {
        $output = $this->version->select('id', 'contentType', 'fileName', 'purgeDate', 'creationDate')
            ->leftJoinRelation('file', 'id as fileId', 'activeVersion')
            ->where('id', '=', $versionId)
            ->toRow();

        if (!$output) {
            throw Exceptional::NotFound(
                'File version ' . $versionId . ' could not be found',
                ['http' => 404]
            );
        }

        if ($output['purgeDate'] !== null) {
            throw Exceptional::Runtime(
                'File version ' . $versionId . ' has been purged'
            );
        }

        $output['isActive'] = $output['id'] == (string)$output['activeVersion'];
        unset($output['activeVersion']);

        return $output;
    }



    public function fetchSingleUserFile($userId, $bucket)
    {
        return $this->file->fetch()
            ->where('owner', '=', $userId)
            ->whereCorrelation('bucket', 'in', 'id')
                ->from($this->bucket, 'bucket')
                ->where('slug', '=', Dictum::slug($bucket))
                ->endCorrelation()
            ->where('bucket', '!=', null)
            ->toRow();
    }

    public function fetchSingleUserVersionForDownload($userId, $bucket)
    {
        $output = $this->version->select('id', 'contentType', 'fileName', 'purgeDate', 'creationDate')
            ->leftJoin('id as fileId')
                ->from($this->file, 'file')
                ->on('file.id', '=', 'version.file')
                ->on('file.activeVersion', '=', 'version.id')
                ->endJoin()
            ->whereCorrelation('file.bucket', 'in', 'id')
                ->from($this->bucket, 'bucket')
                ->where('slug', '=', Dictum::slug($bucket))
                ->endCorrelation()
            ->where('file.bucket', '!=', null)
            ->where('owner', '=', $userId)
            ->orderBy('file.creationDate DESC')
            ->toRow();

        if (!$output) {
            throw Exceptional::NotFound(
                'File version for single context file could not be found',
                ['http' => 404]
            );
        }

        if ($output['purgeDate'] !== null) {
            throw Exceptional::Runtime(
                'File version for single context file has been purged'
            );
        }

        $output['isActive'] = true;
        return $output;
    }

    public function normalizeFileId($fileId, &$transformation = null)
    {
        if (false === strpos($fileId, '[')) {
            $fileId = str_replace('|', '-', $fileId);
        }

        if (preg_match('/^([0-9]+)([-|](\[.*\]))?$/', (string)$fileId, $matches)) {
            $fileId = $matches[1];

            if (isset($matches[3])) {
                $transformation = $matches[3] . '|' . $transformation;
            }

            $test = $this->legacyMap->select('new')
                ->where('old', '=', $fileId)
                ->toValue('new');

            if ($test) {
                $fileId = $test;
            }
        }

        return $fileId;
    }
}
