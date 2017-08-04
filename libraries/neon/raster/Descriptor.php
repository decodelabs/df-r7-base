<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\neon\raster;

use df;
use df\core;
use df\neon;
use df\link;
use df\flex;

class Descriptor implements IDescriptor {

    const DEFAULT_LIFETIME = '3 days';
    const URL_LIFETIME = '1 month';

    protected $_sourceLocation = null;
    protected $_isSourceLocal = true;

    protected $_transformation;
    protected $_location = null;
    protected $_isLocal = true;

    protected $_fileName = null;
    protected $_transformationInFileName = true;
    protected $_contentType = null;

    public function __construct(string $source, string $contentType=null) {
        $this->_sourceLocation = $source;
        $this->_location = $source;
        $this->_contentType = $contentType;

        if(false !== strpos($source, '://')) {
            $parts = explode('://', $source, 2);

            if($parts[0] != 'file') {
                $this->_isSourceLocal = false;
                $this->_isLocal = false;
            }
        } else if(substr($source, 0, 2) == '//') {
            $this->_isSourceLocal = false;
            $this->_isLocal = false;
        }
    }

    public function getSourceLocation(): string {
        return $this->_sourceLocation;
    }

    public function isSourceLocal(): bool {
        return $this->_isSourceLocal;
    }


    public function applyTransformation($transformation, core\time\IDate $modificationDate=null) {
        $mTime = null;
        $fileStore = FileStore::getInstance();

        if($transformation !== null) {
            $transformation = Transformation::factory($transformation);
        }

        $this->_transformation = $transformation;

        if($this->_isSourceLocal) {
            // Local
            $lifetime = static::DEFAULT_LIFETIME;
            $keyPath = core\fs\Dir::stripPathLocation($this->_sourceLocation);
            $key = basename(dirname($keyPath)).'_'.basename($keyPath).'-'.md5($keyPath.':'.$transformation);
            $mTime = filemtime($this->_sourceLocation);

            if($this->_fileName === null) {
                $this->_fileName = basename($this->_sourceLocation);
            }
        } else {
            // Url
            $lifetime = static::URL_LIFETIME;
            $url = new link\http\Url($this->_sourceLocation);
            $path = (string)$url->getPath();
            $key = basename(dirname($path)).'_'.basename($path).'-'.md5($this->_sourceLocation.':'.$transformation);

            if($modificationDate !== null) {
                $mTime = $modificationDate->toTimestamp();
            }

            if($this->_fileName === null) {
                $this->_fileName = $url->getFileName();
            }
        }


        // Prune store
        if($mTime !== null && $mTime > $fileStore->getCreationTime($key)) {
            $fileStore->remove($key);
        }


        // Fetch / create
        if(!$file = $fileStore->get($key, $lifetime)) {
            if(!$this->_isSourceLocal) {
                // Download file
                $http = new link\http\Client();
                $download = core\fs\File::createTemp();
                $response = $http->getFile($this->_sourceLocation, $download);

                if(!$response->isOk()) {
                    throw new RuntimeException(
                        'Unable to fetch remote image for transformation'
                    );
                }

                $this->_location = $download->getPath();
                $this->_isLocal = true;

                if($this->_fileName === null) {
                    $this->_fileName = basename($this->_location);
                }
            }

            $shouldTransform = $transformation !== null && !in_array($this->getContentType(), ['image/svg+xml', 'image/gif']);

            if($shouldTransform) {
                try {
                    $image = Image::loadFile($this->_location)->setOutputFormat('PNG32');
                } catch(FormatException $e) {
                    $image = Image::newCanvas(100, 100, neon\Color::factory('black'));
                }

                $image->transform($transformation)->apply();

                $fileStore->set($key, $image->toString(90));
            } else {
                $fileStore->set($key, $download);
            }

            $file = $fileStore->get($key);

            if(!$this->_isSourceLocal) {
                $download->unlink();
            }
        }


        // Update meta
        $this->_contentType = null;
        $this->_location = $file->getPath();

        if($this->_fileName !== null) {
            $this->getContentType();
            $ext = core\fs\Type::mimeToExt($this->_contentType);

            $path = new core\uri\Path($this->_fileName);
            $origExt = strtolower($path->getExtension());

            if($origExt === 'jpeg') {
                $origExt = 'jpg';
            }

            if($origExt !== $ext) {
                if(strlen($origExt)) {
                    $path->setFileName($path->getFileName().'.'.$origExt);
                }

                $path->setExtension($ext);
            }

            if($this->_transformationInFileName && $transformation !== null) {
                $path->setFileName($path->getFileName().'.'.str_replace([':', '|'], '_', $transformation));
            }

            $this->_fileName = (string)$path;
        }

        return $this;
    }


    public function getTransformation(): ?ITransformation {
        return $this->_transformation;
    }

    public function toIcon(int ...$sizes) {
        if(!$this->_isLocal) {
            $this->applyTransformation(null);
        }

        if($this->getContentType() == 'image/x-icon') {
            return $this;
        }

        if($this->_isSourceLocal) {
            // Local
            $keyPath = core\fs\Dir::stripPathLocation($this->_sourceLocation);
            $key = basename(dirname($keyPath)).'_'.basename($keyPath).'-'.md5($keyPath.':').'-ico';
        } else {
            // Url
            $url = new link\http\Url($this->_sourceLocation);
            $path = (string)$url->getPath();
            $key = basename(dirname($path)).'_'.basename($path).'-'.md5($this->_sourceLocation.':').'-ico';
        }

        $fileStore = FileStore::getInstance();

        if(!$file = $fileStore->get($key, self::DEFAULT_LIFETIME)) {
            $ico = new Ico($this->_location, 16, 32);
            $fileStore->set($key, $ico->generate());
            $file = $fileStore->get($key);
        }

        $this->_location = $file->getPath();
        $this->_contentType = 'image/x-icon';

        if($this->_fileName === null) {
            $this->_fileName = basename($this->_sourceLocation);
        }

        $path = new core\uri\Path($this->_fileName);
        $path->setFileName($path->getFileName().'.'.strtolower($path->getExtension()));
        $path->setExtension('ico');
        $this->_fileName = (string)$path;

        return $this;
    }

    public function getLocation(): string {
        return $this->_location;
    }

    public function isLocal(): bool {
        return $this->_isLocal;
    }


    public function setFileName(?string $fileName) {
        $this->_fileName = $fileName;
        return $this;
    }

    public function getFileName(): string {
        if($this->_fileName === null) {
            $this->_fileName = basename($this->_sourceLocation);
        }

        return $this->_fileName;
    }

    public function shouldIncludeTransformationInFileName(bool $flag=null) {
        if($flag !== null) {
            $this->_transformationInFileName = $flag;
            return $this;
        }

        return $this->_transformationInFileName;
    }

    public function getContentType(): string {
        if($this->_contentType === null) {
            if($this->_isLocal) {
                try {
                    $info = getImageSize($this->_location);
                    $this->_contentType = $info['mime'];
                } catch(\Throwable $e) {
                    $this->_contentType = 'image/png';
                }
            } else {
                $url = new link\http\Url($this->_location);
                $this->_contentType = core\fs\Type::extToMime($url->path->getExtension());

                if(substr($this->_contentType, 0, 6) !== 'image/') {
                    $this->_contentType = 'image/png';
                }
            }
        }

        return $this->_contentType;
    }
}
