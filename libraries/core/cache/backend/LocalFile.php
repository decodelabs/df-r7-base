<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\cache\backend;

use DecodeLabs\Atlas;
use DecodeLabs\Dictum;
use DecodeLabs\Dovetail\Repository;
use DecodeLabs\Genesis;
use DecodeLabs\Glitch;
use DecodeLabs\Terminus\Session;
use df\core;

class LocalFile implements core\cache\IBackend
{
    use core\TValueMap;

    public const PRUNE_LIFETIME = '1 month';

    protected $_lifeTime;
    protected $_cache;
    protected $_dir;

    public static function purgeApp(
        Repository $options,
        ?Session $session = null
    ) {
        self::purgeAll($options);
    }

    public static function purgeAll(
        Repository $options,
        ?Session $session = null
    ) {
        if (!self::isLoadable()) {
            return;
        }

        $path1 = Genesis::$hub->getSharedDataPath() . '/cache/';
        $path2 = Genesis::$hub->getLocalDataPath() . '/cache/';

        Atlas::emptyOut($path1);
        Atlas::emptyOut($path2);
    }

    public static function prune(Repository $options)
    {
        $paths = [
            Genesis::$hub->getSharedDataPath() . '/cache',
            Genesis::$hub->getLocalDataPath() . '/cache'
        ];

        clearstatcache();
        $stamp = core\time\Date::factory('-' . self::PRUNE_LIFETIME)->toTimestamp();
        $output = 0;

        foreach ($paths as $basePath) {
            $baseDir = Atlas::dir($basePath);

            foreach ($baseDir->scanDirs() as $dirName => $dir) {
                foreach ($dir->scanFiles() as $fileName => $file) {
                    if ($file->getLastModified() < $stamp) {
                        $file->delete();
                        $output++;
                    }
                }
            }
        }

        return $output;
    }

    public static function clearFor(
        Repository $options,
        core\cache\ICache $cache
    ) {
        (new self($cache, 0, $options))->clear();
    }

    public static function isLoadable(): bool
    {
        return true;
    }

    public function __construct(
        core\cache\ICache $cache,
        int $lifeTime,
        Repository $options
    ) {
        $this->_cache = $cache;
        $this->_lifeTime = $lifeTime;

        if ($cache->isCacheDistributed()) {
            $path = Genesis::$hub->getSharedDataPath();
        } else {
            $path = Genesis::$hub->getLocalDataPath();
        }

        $path .= '/cache/' . Dictum::fileName($cache->getCacheId());
        $this->_dir = Atlas::createDir($path);
        unset($options);
    }

    public function getConnectionDescription(): string
    {
        return Glitch::normalizePath($this->_dir->getPath());
    }

    public function getStats(): array
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

    public function setLifeTime(int $lifeTime)
    {
        $this->_lifeTime = $lifeTime;
        return $this;
    }

    public function getLifeTime(): int
    {
        return $this->_lifeTime;
    }

    public function set($key, $value, $lifeTime = null)
    {
        $value = serialize($value);
        $key = $this->_normalizeKey($key);
        $file = $this->_dir->createFile('cache-' . $key, $value);

        return true;
    }

    public function get($key, $default = null)
    {
        $key = $this->_normalizeKey($key);
        $file = $this->_dir->getFile('cache-' . $key);
        clearstatcache(false, $file->getPath());

        if (!$file->exists()) {
            return $default;
        }

        if (!$file->hasChanged($this->_lifeTime)) {
            $file->delete();
            return $default;
        }

        $output = $file->getContents();

        try {
            $output = unserialize($output);
        } catch (\Throwable $e) {
            core\logException($e);
            $file->delete();
            return $default;
        }

        return $output;
    }

    public function has(...$keys)
    {
        foreach ($keys as $key) {
            $key = $this->_normalizeKey($key);
            $file = $this->_dir->getFile('cache-' . $key);
            clearstatcache(false, $file->getPath());

            if (!$file->exists()) {
                continue;
            }

            if (!$file->hasChanged($this->_lifeTime)) {
                $file->delete();
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
            $this->_dir->getFile('cache-' . $key)->delete();
        }

        return true;
    }

    public function clear()
    {
        $this->_dir->emptyOut();
        return true;
    }

    public function clearBegins(string $key)
    {
        $key = $this->_normalizeKey($key);
        $length = strlen($key);

        foreach ($this->_dir->scanFiles() as $name => $file) {
            if (substr($name, 6, $length) == $key) {
                $file->delete();
            }
        }

        return true;
    }

    public function clearMatches(string $regex)
    {
        foreach ($this->_dir->scanFiles() as $name => $file) {
            if (preg_match($regex, substr($name, 6))) {
                $file->delete();
            }
        }

        return true;
    }

    public function count(): int
    {
        return $this->_dir->countFiles();
    }

    public function getKeys(): array
    {
        $output = [];

        foreach ($this->_dir->scanFiles() as $name => $file) {
            $output[] = substr($name, 6);
        }

        return $output;
    }

    public function getCreationTime(string $key): ?int
    {
        $key = $this->_normalizeKey($key);
        $file = $this->_dir->getFile('cache-' . $key);
        clearstatcache(false, $file->getPath());

        if (!$file->exists()) {
            return null;
        }

        return $file->getLastModified();
    }

    protected static function _normalizeKey($key)
    {
        return Dictum::fileName($key);
    }
}
