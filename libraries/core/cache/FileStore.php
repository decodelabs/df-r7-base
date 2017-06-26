<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache;

use df;
use df\core;
use df\flex;

abstract class FileStore implements IFileStore {

    use TCache;

    const REGISTRY_PREFIX = 'cache://';
    const IS_DISTRIBUTED = false;

    protected $_dir;

    public function __construct() {
        if(self::IS_DISTRIBUTED) {
            $path = df\Launchpad::$application->getSharedStoragePath();
        } else {
            $path = df\Launchpad::$application->getLocalStoragePath();
        }

        $path .= '/filestore/'.flex\Text::formatFileName($this->getCacheId());
        $this->_dir = core\fs\Dir::create($path);
    }

    public function getCacheStats(): array {
        $count = 0;
        $size = 0;

        foreach($this->_dir->scanFiles() as $file) {
            $count++;
            $size += $file->getSize();
        }

        return [
            'entries' => $count,
            'size' => $size
        ];
    }

    public function set($key, $value) {
        if($value instanceof core\fs\IFile) {
            $value = $value->getContents();
        }

        if(!is_string($value)) {
            throw core\Error::EValue('FileStore values must be strings');
        }

        $key = $this->_normalizeKey($key);
        $this->_dir->createFile('c-'.$key, $value);

        return $this;
    }

    public function get($key, $lifeTime=null): ?core\fs\IFile {
        $key = $this->_normalizeKey($key);
        $file = $this->_dir->getFile('c-'.$key);
        clearstatcache(false, $file->getPath());

        if(!$file->exists()) {
            return null;
        }

        if($lifeTime !== null && !$file->isRecent($lifeTime)) {
            $file->unlink();
            return $default;
        }

        return $file;
    }

    public function has(...$keys) {
        foreach($keys as $key) {
            $key = $this->_normalizeKey($key);
            $file = $this->_dir->getFile('c-'.$key);
            clearstatcache(false, $file->getPath());

            if(!$file->exists()) {
                continue;
            }

            return true;
        }

        return false;
    }

    public function remove(...$keys) {
        foreach($keys as $key) {
            $key = $this->_normalizeKey($key);
            $this->_dir->getFile('c-'.$key)->unlink();
        }

        return $this;
    }

    public function clear() {
        $this->_dir->emptyOut();
        return $this;
    }

    public function clearOlderThan($lifeTime) {
        foreach($this->_dir->scanFiles() as $name => $file) {
            if(!$file->isRecent($lifeTime)) {
                $file->unlink();
            }
        }

        return $this;
    }

    public function clearBegins(string $key) {
        $key = $this->_normalizeKey($key);
        $length = strlen($key);

        foreach($this->_dir->scanFiles() as $name => $file) {
            if(substr($name, 6, $length) == $key) {
                $file->unlink();
            }
        }

        return $this;
    }

    public function clearMatches(string $regex) {
        foreach($this->_dir->scanFiles() as $name => $file) {
            if(preg_match($regex, substr($name, 6))) {
                $file->unlink();
            }
        }

        return $this;
    }

    public function count() {
        return $this->_dir->countFiles();
    }

    public function getKeys(): array {
        $output = [];

        foreach($this->_dir->scanFiles() as $name => $file) {
            $output[] = substr($name, 6);
        }

        return $output;
    }


    public function getCreationTime(string $key): ?int {
        $key = $this->_normalizeKey($key);
        $file = $this->_dir->getFile('c-'.$key);
        clearstatcache(false, $file->getPath());

        if(!$file->exists()) {
            return null;
        }

        return $file->getLastModified();
    }

    protected static function _normalizeKey($key): string {
        return flex\Text::factory($key)
            ->translitToAscii()
            ->replace(' ', '-')
            ->regexReplace('/[\/\\?%*:|"<>]/', '_')
            ->toString();
    }
}
