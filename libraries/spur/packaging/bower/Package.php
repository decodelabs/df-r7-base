<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\packaging\bower;

use df\fuse;

class Package
{
    public $name;
    public $source;
    public $version;
    public $installName;
    public $autoInstallName = false;
    public $isDependency = false;
    public $isRegistry = false;
    public $url;
    public $cacheFileName;
    public $resolver;

    public static function fromThemeDependency(fuse\Dependency $dependency)
    {
        $output = new self($dependency->id, $dependency->source);
        $output->version = $dependency->version;
        $output->installName = null;

        return $output;
    }

    public function __construct(string $name, $source)
    {
        $installName = $name;

        if (false !== strpos($source, '#')) {
            list($source, $version) = explode('#', $source);
            $this->setVersion($version);
        }

        $this->setName($name);
        $this->setInstallName($installName);
        $this->source = $source;
    }

    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getKey()
    {
        return $this->name . '#' . $this->version;
    }

    public function setVersion($version)
    {
        $this->version = $version;
        return $this;
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setInstallName($name)
    {
        $this->installName = $name;
        return $this;
    }

    public function getInstallName()
    {
        return $this->installName;
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function setCacheFileName($fileName)
    {
        $this->cacheFileName = $fileName;
        return $this;
    }

    public function getCacheFileName()
    {
        return $this->cacheFileName;
    }
}
