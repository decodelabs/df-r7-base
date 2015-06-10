<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\flex\code;

use df;
use df\core;

class Location implements ILocation {
    
    protected static $_defaultBlacklist = ['.git'];

    public $id;
    public $path;
    public $blackList = [];
    public $probes = [];

    public function __construct($id, $path, array $blackList=[]) {
        $this->setId($id);
        $this->setPath($path);
        $this->setBlackList($blackList);
    }


// Meta
    public function setId($id) {
        $this->id = $id;
        return $this;
    }
    
    public function getId() {
        return $this->id;
    }
    
    public function setPath($path) {
        $this->path = (string)core\uri\FilePath::factory($path);
        return $this;
    }
    
    public function getPath() {
        return $this->path;
    }
    
    public function setBlackList(array $blackList) {
        foreach($blackList as $i => $path) {
            $blackList[$i] = trim($path, '/');
        }

        $this->blackList = $blackList;
        return $this;
    }
    
    public function getBlackList() {
        return $this->blackList;
    }


// Probes
    public function getProbes() {
        return $this->probes;
    }

// Exec
    public function scan(IScanner $scanner) {
        $this->probes = [];
        $this->_scanPath($scanner, $this->path);
        return $this->probes;
    }

    protected function _scanPath(IScanner $scanner, $path) {
        try {
            $dir = new \DirectoryIterator($path);
        } catch(\Exception $e) {
            return;
        }
        
        foreach($dir as $item) {
            if($item->isDot()) {
                continue;
            }
            
            $pathName = $item->getPathname();
            $localPath = ltrim(substr($pathName, strlen($this->path)), '/');
            
            if(in_array($localPath, $this->blackList)
            || in_array($localPath, self::$_defaultBlacklist)) {
                continue;
            }
            
            if($item->isFile()) {
                foreach($scanner->getProbes() as $id => $probe) {
                    if(isset($this->probes[$id])) {
                        $probe = $this->probes[$id];
                    } else {
                        $this->probes[$id] = $probe = clone $probe;
                    }

                    $probe->probe($this, $localPath);
                }
            } else if($item->isDir()) {
                $this->_scanPath($scanner, $pathName);
            }
        }
    }
}