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

use DecodeLabs\Dictum;
use DecodeLabs\Exceptional;

class Media implements arch\IDirectoryHelper
{
    use arch\TDirectoryHelper;

    protected $_model;

    protected function _init()
    {
        $this->_model = axis\Model::factory('media');
    }

    public function getMediaHandler()
    {
        return $this->_model->getMediaHandler();
    }

    public function getDownloadUrl($fileId)
    {
        if ($fileId === null) {
            return null;
        }

        return $this->context->uri($this->_model->getDownloadUrl($fileId))
            ->setDirectoryRequest(null);
    }

    public function getEmbedUrl($fileId)
    {
        if ($fileId === null) {
            return null;
        }

        return $this->context->uri($this->_model->getEmbedUrl($fileId))
            ->setDirectoryRequest(null);
    }

    public function getVersionDownloadUrl($fileId, $versionId, $isActive)
    {
        if ($fileId === null) {
            return null;
        }

        return $this->context->uri($this->_model->getVersionDownloadUrl($fileId, $versionId, $isActive))
            ->setDirectoryRequest(null);
    }

    public function getImageUrl($fileId, $transformation=null)
    {
        if ($fileId === null) {
            return null;
        }

        return $this->context->uri($this->_model->getImageUrl($fileId, $transformation))
            ->setDirectoryRequest(null);
    }

    public function getVersionImageUrl($fileId, $versionId, $isActive, $transformation=null)
    {
        if ($fileId === null) {
            return null;
        }

        return $this->context->uri($this->_model->getVersionImageUrl($fileId, $versionId, $isActive, $transformation))
            ->setDirectoryRequest(null);
    }


    public function getUploadedUrl($uploadId, $fileName, $transformation=null)
    {
        $output = $this->context->uri->directoryRequest('media/uploaded?id='.$uploadId);
        $output->query->file = $fileName;

        if ($transformation !== null) {
            $output->query->transform = $transformation;
        }

        return $this->context->uri($output);
    }


    public function fetchAndServeDownload($fileId, $embed=false)
    {
        return $this->_serveVersionDownload(
            $this->_model->fetchActiveVersionForDownload($fileId),
            $embed
        );
    }

    public function fetchAndServeVersionDownload($versionId, $embed=false)
    {
        return $this->_serveVersionDownload(
            $this->_model->fetchVersionForDownload($versionId),
            $embed
        );
    }

    protected function _serveVersionDownload(array $version, $embed=false)
    {
        return $this->serveDownload(
            $version['fileId'],
            $version['id'],
            $version['isActive'],
            $version['contentType'],
            $version['fileName'],
            $embed
        );
    }

    public function serveDownload($fileId, $versionId, $isActive, $contentType, $fileName, $embed=false)
    {
        $filePath = $this->getDownloadFileLocation($fileId, $versionId, $isActive);
        $isUrl = $filePath instanceof link\http\IUrl;

        if ($isUrl) {
            $output = $this->context->http->redirect($filePath);
        } else {
            if (!is_file($filePath)) {
                throw Exceptional::{'df/core/fs/NotFound'}([
                    'message' => 'Media file could not be found in storage - this is bad!',
                    'http' => 404
                ]);
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

    public function fetchAndServeImage($fileId, $transformation=null)
    {
        return $this->_serveVersionImage(
            $this->_model->fetchActiveVersionForDownload($fileId),
            $transformation
        );
    }

    public function fetchAndServeVersionImage($versionId, $transformation=null)
    {
        return $this->_serveVersionImage(
            $this->_model->fetchVersionForDownload($versionId),
            $transformation
        );
    }

    protected function _serveVersionImage(array $version, $transformation=null)
    {
        if ($transformation === null && isset($version['transformation'])) {
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

    public function serveImage($fileId, $versionId, $isActive, $contentType, $fileName=null, $transformation=null, $modificationDate=null)
    {
        try {
            $filePath = $this->getDownloadFileLocation($fileId, $versionId, $isActive);
        } catch (core\fs\NotFoundException $e) {
            if (!df\Launchpad::$app->isProduction()) {
                return $this->serveFallbackImage($contentType, $fileName, $transformation);
            }

            throw $e;
        }

        $descriptor = new neon\raster\Descriptor($filePath, $contentType);
        $descriptor->setFileName($fileName);

        if ($transformation !== null) {
            $descriptor->applyTransformation($transformation, core\time\Date::normalize($modificationDate));
        }

        $location = $descriptor->getLocation();

        if (!$descriptor->isLocal()) {
            $output = $this->context->http->redirect($location);
        } else {
            if ($transformation !== null) {
                $namePath = core\uri\Path::factory($fileName);

                $fileName = (string)$namePath->setFileName(
                    $namePath->getFileName().' '.Dictum::filename($transformation)
                );
            }

            $output = $this->context->http->fileResponse($location)
                ->setContentType($descriptor->getContentType())
                ->setFileName($descriptor->getFileName());

            $output->getHeaders()
                ->set('Access-Control-Allow-Origin', '*')
                ->setCacheAccess('public')
                ->canStoreCache(true)
                ->setCacheExpiration('+1 hour');
        }

        return $output;
    }

    public function serveFallbackImage(string $contentType, string $fileName=null, $transformation=null)
    {
        switch ($contentType) {
            case 'image/svg+xml':
                $file = $this->_generateFallbackSvg();
                break;

            default:
                $file = $this->_generateFallbackRaster();
                $contentType = 'image/jpg';
                break;
        }

        $output = $this->context->http->stringResponse($file, $contentType)
            ->setFileName($fileName);

        $output->getHeaders()
            ->set('Access-Control-Allow-Origin', '*')
            ->setCacheAccess('no-cache')
            ->shouldRevalidateCache(true)
            ->canStoreCache(false);

        return $output;
    }

    protected function _generateFallbackSvg()
    {
        return
            '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'."\n".
            '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'."\n".
            '<svg width="500" height="500" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1">'."\n".
            '<rect x="0" y="0" width="500" height="500" fill="#DDD"/>'."\n".
            '</svg>';
    }

    protected function _generateFallbackRaster()
    {
        return neon\raster\Image::newCanvas(500, 500, '#DDD')
            ->setOutputFormat('JPEG')
            ->toString(10);
    }

    public function getImageFilePath($fileId, $versionId, $isActive, $contentType, $transformation=null, $modificationDate=null)
    {
        $filePath = $this->getDownloadFileLocation($fileId, $versionId, $isActive);

        $descriptor = new neon\raster\Descriptor($filePath, $contentType);
        $descriptor->applyTransformation($transformation, core\time\Date::normalize($modificationDate));

        return $descriptor->getLocation();
    }

    public function image($fileId, $transformation=null, $alt=null, $width=null, $height=null)
    {
        return $this->context->html->image($this->getImageUrl($fileId, $transformation), $alt, $width, $height);
    }

    public function getDownloadFileLocation($fileId, $versionId, $isActive)
    {
        $handler = $this->_model->getMediaHandler();

        if ($handler instanceof neon\mediaHandler\ILocalDataHandler) {
            $filePath = $handler->getFilePath($fileId, $versionId);

            if (!is_file($filePath)) {
                throw Exceptional::{'df/core/fs/NotFound'}([
                    'message' => 'Media file could not be found in storage - this is bad!',
                    'http' => 404
                ]);
            }
        } else {
            $filePath = link\http\Url::factory($handler->getVersionDownloadUrl($fileId, $versionId, $isActive));
        }

        return $filePath;
    }


    public function newImageTransformation($transformation=null)
    {
        return neon\raster\Transformation::factory($transformation);
    }
}
