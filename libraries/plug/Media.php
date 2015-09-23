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
        return $this->context->uri($this->_model->getDownloadUrl($fileId))
            ->setDirectoryRequest(null);
    }

    public function getVersionDownloadUrl($fileId, $versionId, $isActive) {
        return $this->context->uri($this->_model->getVersionDownloadUrl($fileId, $versionId, $isActive))
            ->setDirectoryRequest(null);
    }

    public function getImageUrl($fileId, $transformation=null) {
        return $this->context->uri($this->_model->getImageUrl($fileId, $transformation))
            ->setDirectoryRequest(null);
    }

    public function getVersionImageUrl($fileId, $versionId, $isActive, $transformation=null) {
        return $this->context->uri($this->_model->getVersionImageUrl($fileId, $versionId, $isActive, $transformation))
            ->setDirectoryRequest(null);
    }

    public function fetchAndServeDownload($fileId) {
        return $this->_serveVersionDownload(
            $this->_model->fetchActiveVersionForDownload($fileId)
        );
    }

    public function fetchAndServeVersionDownload($versionId) {
        return $this->_serveVersionDownload(
            $this->_model->fetchVersionForDownload($versionId)
        );
    }

    protected function _serveVersionDownload(array $version) {
        return $this->serveDownload(
            $version['fileId'], 
            $version['id'], 
            $version['isActive'],
            $version['contentType'],
            $version['fileName']
        );
    }

    public function serveDownload($fileId, $versionId, $isActive, $contentType, $fileName) {
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
                ->setAttachmentFileName($fileName);

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
            $transformation,
            $version['creationDate']
        );
    }

    public function serveImage($fileId, $versionId, $isActive, $contentType, $transformation=null, $modificationDate=null) {
        $filePath = $this->_getImageFileLocation($fileId, $versionId, $isActive, $contentType, $transformation, $modificationDate);
        $isUrl = $filePath instanceof link\http\IUrl;

        if($isUrl) {
            $output = $this->context->http->redirect($filePath);
        } else {
            $output = $this->context->http->fileResponse($filePath)
                ->setContentType($contentType);

            $output->getHeaders()
                ->set('Access-Control-Allow-Origin', '*')
                ->setCacheAccess('public')
                ->canStoreCache(true)
                ->setCacheExpiration('+1 year');
        }
        
        return $output;
    }

    public function getImageFilePath($fileId, $versionId, $isActive, $contentType, $transformation=null, $modificationDate=null) {
        return $this->_getImageFileLocation($fileId, $versionId, $isActive, $contentType, $transformation, $modificationDate, true);
    }

    public function image($fileId, $alt=null, $width=null, $height=null) {
        return $this->context->html->image($this->getDownloadUrl($fileId), $alt, $width, $height);
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

        if(($forceLocal && $isUrl) || ($transformation !== null && $contentType != 'image/svg+xml')) {
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