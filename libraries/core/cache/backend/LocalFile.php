<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache\backend;

use df;
use df\core;
use df\flex;

class LocalFile implements core\cache\IBackend {

    use core\TValueMap;

    const PRUNE_LIFETIME = '1 month';

    protected $_lifeTime;
    protected $_cache;
    protected $_dir;

    public static function purgeApp(core\collection\ITree $options) {
        self::purgeAll($options);
    }

    public static function purgeAll(core\collection\ITree $options) {
        if(!self::isLoadable()) {
            return;
        }

        $path1 = df\Launchpad::$app->getSharedDataPath().'/cache/';
        $path2 = df\Launchpad::$app->getLocalDataPath().'/cache/';

        core\fs\Dir::deleteContents($path1);
        core\fs\Dir::deleteContents($path2);
    }

    public static function prune(core\collection\ITree $options) {
        $paths = [
            df\Launchpad::$app->getSharedDataPath().'/cache',
            df\Launchpad::$app->getLocalDataPath().'/cache'
        ];

        clearstatcache();
        $stamp = core\time\Date::factory('-'.self::PRUNE_LIFETIME)->toTimestamp();
        $output = 0;

        foreach($paths as $basePath) {
            $baseDir = core\fs\Dir::factory($basePath);

            foreach($baseDir->scanDirs() as $dirName => $dir) {
                foreach($dir->scanFiles() as $fileName => $file) {
                    if($file->getLastModified() < $stamp) {
                        $file->unlink();
                        $output++;
                    }
                }
            }
        }

        return $output;
    }

    public static function clearFor(core\collection\ITree $options, core\cache\ICache $cache) {
        (new self($cache, 0, $options))->clear();
    }

    public static function isLoadable(): bool {
        return true;
    }

    public function __construct(core\cache\ICache $cache, int $lifeTime, core\collection\ITree $options) {
        $this->_cache = $cache;
        $this->_lifeTime = $lifeTime;

        if($cache->isCacheDistributed()) {
            $path = df\Launchpad::$app->getSharedDataPath();
        } else {
            $path = df\Launchpad::$app->getLocalDataPath();
        }

        $path .= '/cache/'.flex\Text::formatFileName($cache->getCacheId());
        $this->_dir = core\fs\Dir::create($path);
    }

    public function getConnectionDescription(): string {
        return $this->_dir->getLocationPath();
    }

    public function getStats(): array {
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

    public function setLifeTime(int $lifeTime) {
        $this->_lifeTime = $lifeTime;
        return $this;
    }

    public function getLifeTime(): int {
        return $this->_lifeTime;
    }

    public function set($key, $value) {
        $value = serialize($value);
        $key = $this->_normalizeKey($key);
        $file = $this->_dir->createFile('cache-'.$key, $value);

        return true;
    }

    public function get($key, $default=null) {
        $key = $this->_normalizeKey($key);
        $file = $this->_dir->getFile('cache-'.$key);
        clearstatcache(false, $file->getPath());

        if(!$file->exists()) {
            return $default;
        }

        if(!$file->isRecent($this->_lifeTime)) {
            $file->unlink();
            return $default;
        }

        $output = $file->getContents();

        try {
            $output = unserialize($output);
        } catch(\Throwable $e) {
            core\logException($e);
            $file->unlink();
            return $default;
        }

        return $output;
    }

    public function has(...$keys) {
        foreach($keys as $key) {
            $key = $this->_normalizeKey($key);
            $file = $this->_dir->getFile('cache-'.$key);
            clearstatcache(false, $file->getPath());

            if(!$file->exists()) {
                continue;
            }

            if(!$file->isRecent($this->_lifeTime)) {
                $file->unlink();
                continue;
            }

            return true;
        }

        return false;
    }

    public function remove(...$keys) {
        foreach($keys as $key) {
            $key = $this->_normalizeKey($key);
            $this->_dir->getFile('cache-'.$key)->unlink();
        }

        return true;
    }

    public function clear() {
        $this->_dir->emptyOut();
        return true;
    }

    public function clearBegins(string $key) {
        $key = $this->_normalizeKey($key);
        $length = strlen($key);

        foreach($this->_dir->scanFiles() as $name => $file) {
            if(substr($name, 6, $length) == $key) {
                $file->unlink();
            }
        }

        return true;
    }

    public function clearMatches(string $regex) {
        foreach($this->_dir->scanFiles() as $name => $file) {
            if(preg_match($regex, substr($name, 6))) {
                $file->unlink();
            }
        }

        return true;
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
        $file = $this->_dir->getFile('cache-'.$key);
        clearstatcache(false, $file->getPath());

        if(!$file->exists()) {
            return null;
        }

        return $file->getLastModified();
    }

    protected static function _normalizeKey($key) {
        return flex\Text::formatFileName($key);
    }
}
