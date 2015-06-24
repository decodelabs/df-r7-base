<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\spur\packaging\bower;

use df;
use df\core;
use df\spur;

class Package implements IPackage {
    
    public $name;
    public $source;
    public $version;
    public $installName;
    public $url;
    public $cacheFileName;

    public function __construct($name, $source) {
        $installName = $name;

        if(false !== strpos($source, '#')) {
            list($source, $version) = explode('#', $source);
            $this->setVersion($version);
        }

        $this->setName($name);
        $this->setInstallName($installName);
        $this->source = $source;
    }

    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function getName() {
        return $this->name;
    }

    public function setVersion($version) {
        $this->version = $version;
        return $this;
    }

    public function getVersion() {
        return $this->version;
    }

    public function setInstallName($name) {
        $this->installName = $name;
        return $this;
    }

    public function getInstallName() {
        return $this->installName;
    }

    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    public function getUrl() {
        return $this->url;
    }

    public function setCacheFileName($fileName) {
        $this->cacheFileName = $fileName;
        return $this;
    }

    public function getCacheFileName() {
        return $this->cacheFileName;
    }
}