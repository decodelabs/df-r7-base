<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache;

use df;
use df\core;
use df\flex;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\File;

abstract class FileStore implements IFileStore
{
    use TCache;

    const REGISTRY_PREFIX = 'cache://';
    const IS_DISTRIBUTED = false;

    protected $_dir;

    public static function prune($lifeTime='1 month'): int
    {
        $total = 0;
        $dirs = [
            df\Launchpad::$app->getLocalDataPath().'/filestore/',
            df\Launchpad::$app->getSharedDataPath().'/filestore/',
        ];

        foreach ($dirs as $path) {
            $dir = Atlas::$fs->dir($path);

            if (!$dir->exists()) {
                continue;
            }

            foreach ($dir->scanDirs() as $inner) {
                foreach ($inner->scanFiles() as $name => $file) {
                    if (!$file->hasChangedIn($lifeTime)) {
                        $file->delete();
                        $total++;
                    }
                }
            }
        }

        return $total;
    }

    public static function purgeAll(): void
    {
        $dirs = [
            df\Launchpad::$app->getLocalDataPath().'/filestore/',
            df\Launchpad::$app->getSharedDataPath().'/filestore/',
        ];

        foreach ($dirs as $path) {
            Atlas::$fs->deleteDir($path);
        }
    }

    public function __construct()
    {
        if (static::IS_DISTRIBUTED) {
            $path = df\Launchpad::$app->getSharedDataPath();
        } else {
            $path = df\Launchpad::$app->getLocalDataPath();
        }

        $path .= '/filestore/'.flex\Text::formatFileName($this->getCacheId());
        $this->_dir = Atlas::$fs->createDir($path);
    }

    public function getCacheStats(): array
    {
        $count = 0;
        $size = 0;

        foreach ($this->_dir->scanFiles() as $file) {
            $count++;
            $size += $file->getSize();
        }

        return [
            'entries' => $count,
            'size' => $size
        ];
    }

    public function set($key, $value)
    {
        if (!$value instanceof File) {
            try {
                $value = (string)$value;
            } catch (\Throwable $e) {
                throw core\Error::EValue('FileStore value must be Atlas File or string');
            }
        } else {
            $value = $value->getContents();
        }

        $key = $this->_normalizeKey($key);
        $this->_dir->createFile('c-'.$key, $value);

        return $this;
    }

    public function get($key, $lifeTime=null): ?File
    {
        $key = $this->_normalizeKey($key);
        $file = $this->_dir->getFile('c-'.$key);
        clearstatcache(false, $file->getPath());

        if (!$file->exists()) {
            return null;
        }

        if ($lifeTime !== null && !$file->hasChangedIn($lifeTime)) {
            $file->delete();
            return null;
        }

        return $file;
    }

    public function has(...$keys)
    {
        foreach ($keys as $key) {
            $key = $this->_normalizeKey($key);
            $file = $this->_dir->getFile('c-'.$key);
            clearstatcache(false, $file->getPath());

            if (!$file->exists()) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function remove(...$keys)
    {
        foreach ($keys as $key) {
            $key = $this->_normalizeKey($key);
            $this->_dir->getFile('c-'.$key)->delete();
        }

        return $this;
    }

    public function clear()
    {
        $this->_dir->emptyOut();
        return $this;
    }

    public function clearOlderThan($lifeTime)
    {
        foreach ($this->_dir->scanFiles() as $name => $file) {
            if (!$file->hasChangedIn($lifeTime)) {
                $file->delete();
            }
        }

        return $this;
    }

    public function clearBegins(string $key)
    {
        $key = $this->_normalizeKey($key);
        $length = strlen($key);

        foreach ($this->_dir->scanFiles() as $name => $file) {
            if (substr($name, 2, $length) == $key) {
                $file->delete();
            }
        }

        return $this;
    }

    public function clearMatches(string $regex)
    {
        foreach ($this->_dir->scanFiles() as $name => $file) {
            if (preg_match($regex, substr($name, 2))) {
                $file->delete();
            }
        }

        return $this;
    }

    public function count()
    {
        return $this->_dir->countFiles();
    }

    public function getKeys(): array
    {
        $output = [];

        foreach ($this->_dir->scanFiles() as $name => $file) {
            $output[] = substr($name, 2);
        }

        return $output;
    }

    public function getFileList(): array
    {
        $output = [];

        foreach ($this->_dir->scanFiles() as $name => $file) {
            $key = substr($name, 2);
            $output[$key] = $file;
        }

        return $output;
    }


    public function getCreationTime(string $key): ?int
    {
        $key = $this->_normalizeKey($key);
        $file = $this->_dir->getFile('c-'.$key);
        clearstatcache(false, $file->getPath());

        if (!$file->exists()) {
            return null;
        }

        return $file->getLastModified();
    }

    protected static function _normalizeKey($key): string
    {
        return flex\Text::factory($key)
            ->translitToAscii()
            ->replace(' ', '-')
            ->regexReplace('/[\/\\?%*:|"<>]/', '_')
            ->toString();
    }
}
