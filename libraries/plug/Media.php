<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\plug;

use df;
use df\core;
use df\plug;
use df\arch;
use df\fire;
use df\axis;
use df\neon;
use df\link;

class Media implements arch\IDirectoryHelper {

    use arch\TDirectoryHelper;

    protected $_model;

    protected function _init() {
        $this->_model = axis\Model::factory('media');
    }

    public function getMediaHandler() {
        return $this->_model->getMediaHandler();
    }

    public function getDownloadUrl($fileId) {
        if($fileId === null) {
            return null;
        }

        return $this->context->uri($this->_model->getDownloadUrl($fileId))
            ->setDirectoryRequest(null);
    }

    public function getEmbedUrl($fileId) {
        if($fileId === null) {
            return null;
        }

        return $this->context->uri($this->_model->getEmbedUrl($fileId))
            ->setDirectoryRequest(null);
    }

    public function getVersionDownloadUrl($fileId, $versionId, $isActive) {
        if($fileId === null) {
            return null;
        }

        return $this->context->uri($this->_model->getVersionDownloadUrl($fileId, $versionId, $isActive))
            ->setDirectoryRequest(null);
    }

    public function getImageUrl($fileId, $transformation=null) {
        if($fileId === null) {
            return null;
        }

        return $this->context->uri($this->_model->getImageUrl($fileId, $transformation))
            ->setDirectoryRequest(null);
    }

    public function getVersionImageUrl($fileId, $versionId, $isActive, $transformation=null) {
        if($fileId === null) {
            return null;
        }

        return $this->context->uri($this->_model->getVersionImageUrl($fileId, $versionId, $isActive, $transformation))
            ->setDirectoryRequest(null);
    }


    public function getUploadedUrl($uploadId, $fileName, $transformation=null) {
        $output = $this->context->uri->directoryRequest('media/uploaded?id='.$uploadId);
        $output->query->file = $fileName;

        if($transformation !== null) {
            $output->query->transform = $transformation;
        }

        return $this->context->uri($output);
    }


    public function fetchAndServeDownload($fileId, $embed=false) {
        return $this->_serveVersionDownload(
            $this->_model->fetchActiveVersionForDownload($fileId),
            $embed
        );
    }

    public function fetchAndServeVersionDownload($versionId, $embed=false) {
        return $this->_serveVersionDownload(
            $this->_model->fetchVersionForDownload($versionId),
            $embed
        );
    }

    protected function _serveVersionDownload(array $version, $embed=false) {
        return $this->serveDownload(
            $version['fileId'],
            $version['id'],
            $version['isActive'],
            $version['contentType'],
            $version['fileName'],
            $embed
        );
    }

    public function serveDownload($fileId, $versionId, $isActive, $contentType, $fileName, $embed=false) {
        $filePath = $this->_getDownloadFileLocation($fileId, $versionId, $isActive);
        $isUrl = $filePath instanceof link\http\IUrl;

        if($isUrl) {
            $output = $this->context->http->redirect($filePath);
        } else {
            if(!is_file($filePath)) {
                $this->context->throwError(404, 'Media file could not be found in storage - this is bad!');
            }

            $output = $this->context->http->fileResponse($filePath)
                ->setContentType($contentType)
                ->setFileName($fileName, !$embed)
                ;

            $output->getHeaders()
                ->set('Access-Control-Allow-Origin', '*')
                ->setCacheAccess('public')
                ->canStoreCache(true)
                ->setCacheExpiration('+1 year');
        }

        return $output;
    }

    public function fetchAndServeImage($fileId, $transformation=null) {
        return $this->_serveVersionImage(
            $this->_model->fetchActiveVersionForDownload($fileId),
            $transformation
        );
    }

    public function fetchAndServeVersionImage($versionId, $transformation=null) {
        return $this->_serveVersionImage(
            $this->_model->fetchVersionForDownload($versionId),
            $transformation
        );
    }

    protected function _serveVersionImage(array $version, $transformation=null) {
        if($transformation === null && isset($version['transformation'])) {
            $transformation = $version['transformation'];
        }

        return $this->serveImage(
            $version['fileId'],
            $version['id'],
            $version['isActive'],
            $version['contentType'],
            $version['fileName'],
            $transformation,
            $version['creationDate']
        );
    }

    public function serveImage($fileId, $versionId, $isActive, $contentType, $fileName=null, $transformation=null, $modificationDate=null) {
        $filePath = $this->_getImageFileLocation($fileId, $versionId, $isActive, $contentType, $transformation, $modificationDate);
        $isUrl = $filePath instanceof link\http\IUrl;

        if($isUrl) {
            $output = $this->context->http->redirect($filePath);
        } else {
            $output = $this->context->http->fileResponse($filePath)
                ->setContentType($contentType)
                ->setFileName($fileName);

            $output->getHeaders()
                ->set('Access-Control-Allow-Origin', '*')
                ->setCacheAccess('public')
                ->canStoreCache(true)
                ->setCacheExpiration('+1 hour');
        }

        return $output;
    }

    public function getImageFilePath($fileId, $versionId, $isActive, $contentType, $transformation=null, $modificationDate=null) {
        return $this->_getImageFileLocation($fileId, $versionId, $isActive, $contentType, $transformation, $modificationDate, true);
    }

    public function image($fileId, $transformation=null, $alt=null, $width=null, $height=null) {
        return $this->context->html->image($this->getImageUrl($fileId, $transformation), $alt, $width, $height);
    }

    protected function _getDownloadFileLocation($fileId, $versionId, $isActive) {
        $handler = $this->_model->getMediaHandler();

        if($handler instanceof neon\mediaHandler\ILocalDataHandler) {
            $filePath = $handler->getFilePath($fileId, $versionId);

            if(!is_file($filePath)) {
                $this->context->throwError(404, 'Media file could not be found in storage - this is bad!');
            }
        } else {
            $filePath = link\http\Url::factory($handler->getVersionDownloadUrl($fileId, $versionId, $isActive));
        }

        return $filePath;
    }

    protected function _getImageFileLocation($fileId, $versionId, $isActive, $contentType, $transformation=null, $modificationDate=null, $forceLocal=false) {
        $handler = $this->_model->getMediaHandler();
        $filePath = $this->_getDownloadFileLocation($fileId, $versionId, $isActive);
        $isUrl = $filePath instanceof link\http\IUrl;

        if(($forceLocal && $isUrl) || ($transformation !== null && !in_array($contentType, ['image/svg+xml', 'image/gif']))) {
            $cache = neon\raster\Cache::getInstance();

            if($modificationDate !== null) {
                $modificationDate = core\time\Date::factory($modificationDate);
            }

            $filePath = $cache->getTransformationFilePath($filePath, $transformation, $modificationDate);
            $contentType = 'image/png';
            $isUrl = false;
        }

        return $filePath;
    }
}