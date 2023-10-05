<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\neon\raster;

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;

use DecodeLabs\Spectrum\Color;
use DecodeLabs\Typify;
use df\core;
use df\link;

class Descriptor implements IDescriptor
{
    public const DEFAULT_LIFETIME = '3 days';
    public const URL_LIFETIME = '1 month';

    public const ALPHA_TYPES = [
        'image/gif',
        'image/png',
        'image/svg+xml',
        'image/tiff',
        'image/x-icon',
        'image/x-targa'
    ];

    protected $_sourceLocation = null;
    protected $_isSourceLocal = true;

    protected $_transformation;
    protected $_optimizeTransformation = true;
    protected $_location = null;
    protected $_isLocal = true;

    protected $_fileName = null;
    protected $_transformationInFileName = true;
    protected $_contentType = null;

    public function __construct(string $source, string $contentType = null)
    {
        $this->_sourceLocation = $source;
        $this->_location = $source;
        $this->_contentType = $contentType;

        if (false !== strpos($source, '://')) {
            $parts = explode('://', $source, 2);

            if ($parts[0] != 'file') {
                $this->_isSourceLocal = false;
                $this->_isLocal = false;
            }
        } elseif (substr($source, 0, 2) == '//') {
            $this->_isSourceLocal = false;
            $this->_isLocal = false;
        }
    }

    public function getSourceLocation(): string
    {
        return $this->_sourceLocation;
    }

    public function isSourceLocal(): bool
    {
        return $this->_isSourceLocal;
    }


    public function applyTransformation($transformation, core\time\IDate $modificationDate = null)
    {
        $mTime = null;
        $fileStore = FileStore::getInstance();

        if ($transformation !== null) {
            $transformation = Transformation::factory($transformation);
        }

        $this->_transformation = $transformation;

        if ($this->_isSourceLocal) {
            // Local
            $lifetime = static::DEFAULT_LIFETIME;
            $keyPath = $this->normalizePath($this->_sourceLocation);
            $key = basename(dirname($keyPath)) . '_' . basename($keyPath) . '-' . md5($keyPath . ':' . $transformation);
            $mTime = filemtime($this->_sourceLocation);

            if ($this->_fileName === null) {
                $this->_fileName = basename($this->_sourceLocation);
            }
        } else {
            // Url
            $lifetime = static::URL_LIFETIME;
            $url = new link\http\Url($this->_sourceLocation);
            $path = (string)$url->getPath();
            $key = basename(dirname($path)) . '_' . basename($path) . '-' . md5($this->_sourceLocation . ':' . $transformation);

            if ($modificationDate !== null) {
                $mTime = $modificationDate->toTimestamp();
            }

            if ($this->_fileName === null) {
                $this->_fileName = $url->getPath()->getFileName();
            }
        }


        // Prune store
        if ($mTime !== null && $mTime > $fileStore->getCreationTime($key)) {
            $fileStore->remove($key);
        }


        // Fetch / create
        $type = $this->getContentType();
        $isTransformable = !in_array($type, ['image/svg+xml', 'image/gif']);

        if ($this->_isSourceLocal && !$isTransformable) {
            $file = Atlas::file($this->_sourceLocation);
        } elseif (!$file = $fileStore->get($key, $lifetime)) {
            $download = null;

            if (!$this->_isSourceLocal) {
                // Download file
                $download = Atlas::$http->getTempFile($this->_sourceLocation, [
                    'verify' => false
                ]);

                $this->_location = $download->getPath();
                $this->_isLocal = true;

                if ($this->_fileName === null) {
                    $this->_fileName = basename($this->_location);
                }

                try {
                    $info = getImageSize($this->_location);
                    $this->_contentType = $info['mime'];
                } catch (\Throwable $e) {
                }
            }


            if ($transformation !== null && $isTransformable) {
                $isAlphaType = in_array($type, self::ALPHA_TYPES);

                try {
                    $image = Image::loadFile($this->_location);
                } catch (FormatException $e) {
                    $image = Image::newCanvas(100, 100, Color::create('black'));
                }

                if (!$isAlphaType && $transformation->isAlphaRequired()) {
                    $image->setOutputFormat('PNG32');
                } elseif ($this->_optimizeTransformation && !$isAlphaType) {
                    $image->setOutputFormat('JPEG');
                }

                $image->transform($transformation)->apply();
                $fileStore->set($key, $image->toString(90));
            } else {
                $fileStore->set($key, Atlas::file($this->_location));
            }

            $file = $fileStore->get($key);

            /** @phpstan-ignore-next-line */
            if (!$this->_isSourceLocal && $download) {
                $download->delete();
            }
        }


        // Update meta
        if ($type != 'image/svg+xml') {
            $this->_contentType = null;
        }

        $this->_location = $file->getPath();

        if ($this->_fileName !== null) {
            $this->getContentType();
            $ext = Typify::getExtensionFor($this->_contentType);

            if ($ext === 'jpeg') {
                $ext = 'jpg';
            }

            $path = new core\uri\Path($this->_fileName);
            $origExt = strtolower((string)$path->getExtension());


            if ($origExt === 'jpeg') {
                $origExt = 'jpg';
            }

            if ($origExt !== $ext) {
                if (strlen($origExt)) {
                    $path->setFileName($path->getFileName() . '.' . $origExt);
                }

                $path->setExtension($ext);
            }

            if ($this->_transformationInFileName && $transformation !== null) {
                $path->setFileName($path->getFileName() . '.' . str_replace([':', '|'], '_', $transformation));
            }

            $this->_fileName = (string)$path;
        }

        return $this;
    }

    protected function normalizePath(?string $path)
    {
        if ($path === null) {
            return $path;
        }

        $locations = [
            'root' => dirname(Genesis::$build->path),
            'app' => Genesis::$hub->getApplicationPath()
        ];

        $path = (string)preg_replace('/[[:^print:]]/', '', $path);

        foreach ($locations as $key => $match) {
            if (substr($path, 0, $len = strlen($match)) == $match) {
                $innerPath = substr(str_replace('\\', '/', $path), $len + 1);

                if (
                    Genesis::$build->isCompiled() &&
                    $key == 'root'
                ) {
                    $parts = explode('/', $innerPath);
                    array_shift($parts);
                    $innerPath = implode('/', $parts);
                }

                $path = $key . '://' . $innerPath;
                break;
            }
        }

        return $path;
    }

    public function shouldOptimizeTransformation(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_optimizeTransformation = $flag;
            return $this;
        }

        return $this->_optimizeTransformation;
    }

    public function getTransformation(): ?ITransformation
    {
        return $this->_transformation;
    }

    public function toIcon(int ...$sizes)
    {
        if (!$this->_isLocal) {
            $this->applyTransformation(null);
        }

        if ($this->getContentType() == 'image/x-icon') {
            return $this;
        }

        if ($this->_isSourceLocal) {
            // Local
            $keyPath = $this->normalizePath($this->_sourceLocation);
            $key = basename(dirname($keyPath)) . '_' . basename($keyPath) . '-' . md5($keyPath . ':') . '-ico';
        } else {
            // Url
            $url = new link\http\Url($this->_sourceLocation);
            $path = (string)$url->getPath();
            $key = basename(dirname($path)) . '_' . basename($path) . '-' . md5($this->_sourceLocation . ':') . '-ico';
        }

        $fileStore = FileStore::getInstance();

        if (!$file = $fileStore->get($key, self::DEFAULT_LIFETIME)) {
            $ico = new Ico($this->_location, 16, 32);
            $fileStore->set($key, $ico->generate());
            $file = $fileStore->get($key);
        }

        $this->_location = $file->getPath();
        $this->_contentType = 'image/x-icon';

        if ($this->_fileName === null) {
            $this->_fileName = basename($this->_sourceLocation);
        }

        $path = new core\uri\Path($this->_fileName);
        $path->setFileName($path->getFileName() . '.' . strtolower((string)$path->getExtension()));
        $path->setExtension('ico');
        $this->_fileName = (string)$path;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->_location;
    }

    public function isLocal(): bool
    {
        return $this->_isLocal;
    }


    public function setFileName(?string $fileName)
    {
        $this->_fileName = $fileName;
        return $this;
    }

    public function getFileName(): string
    {
        if ($this->_fileName === null) {
            $this->_fileName = basename($this->_sourceLocation);
        }

        return $this->_fileName;
    }

    public function shouldIncludeTransformationInFileName(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_transformationInFileName = $flag;
            return $this;
        }

        return $this->_transformationInFileName;
    }

    public function getContentType(): string
    {
        if ($this->_contentType === null) {
            if ($this->_isLocal) {
                try {
                    $info = getImageSize($this->_location);
                    $this->_contentType = $info['mime'];
                } catch (\Throwable $e) {
                    $this->_contentType = 'image/png';
                }
            } else {
                $url = new link\http\Url($this->_location);
                $this->_contentType = Typify::detect($url->path->getExtension());
            }

            if ($this->_contentType === null && $this->_fileName) {
                $this->_contentType = Typify::detect($this->_fileName);
            }

            if (substr($this->_contentType, 0, 6) !== 'image/') {
                $this->_contentType = 'image/png';
            }
        }

        return $this->_contentType;
    }
}
