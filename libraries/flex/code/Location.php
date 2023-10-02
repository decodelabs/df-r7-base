<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\flex\code;

use df\core;

class Location
{
    public const DEFAULT_BLACKLIST = ['.git'];

    public $id;
    public $path;
    public $blackList = [];
    public $probes = [];

    public function __construct(string $id, $path, array $blackList = [])
    {
        $this->setId($id);
        $this->setPath($path);
        $this->setBlackList($blackList);
    }


    // Meta
    public function setId(string $id)
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setPath($path)
    {
        $this->path = (string)core\uri\Path::factory($path);
        return $this;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function setBlackList(array $blackList)
    {
        foreach ($blackList as $i => $path) {
            $blackList[$i] = trim((string)$path, '/');
        }

        $this->blackList = $blackList;
        return $this;
    }

    public function getBlackList()
    {
        return $this->blackList;
    }


    // Probes
    public function getProbes()
    {
        return $this->probes;
    }

    // Exec
    public function scan(IScanner $scanner)
    {
        $this->probes = [];
        $this->_scanPath($scanner, $this->path);
        return $this->probes;
    }

    protected function _scanPath(IScanner $scanner, $path)
    {
        try {
            $dir = new \DirectoryIterator($path);
        } catch (\Throwable $e) {
            return;
        }

        foreach ($dir as $item) {
            if ($item->isDot()) {
                continue;
            }

            $pathName = $item->getPathname();
            $localPath = ltrim(substr($pathName, strlen($this->path)), '/');

            if (in_array($localPath, $this->blackList)
            || in_array($localPath, self::DEFAULT_BLACKLIST)) {
                continue;
            }

            if ($item->isFile()) {
                foreach ($scanner->getProbes() as $id => $probe) {
                    if (isset($this->probes[$id])) {
                        $probe = $this->probes[$id];
                    } else {
                        $this->probes[$id] = $probe = clone $probe;
                    }

                    $probe->probe($this, $localPath);
                }
            } elseif ($item->isDir()) {
                $this->_scanPath($scanner, $pathName);
            }
        }
    }
}
