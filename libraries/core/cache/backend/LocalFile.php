<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache\backend;

use df;
use df\core;
    
class LocalFile implements core\cache\IDirectFileBackend {

    use core\TValueMap;

    const PRUNE_LIFETIME = '1 month';
    
    protected $_lifeTime;
    protected $_cache;
    protected $_dir;
    protected $_serialize = true;

    public static function purgeApp(core\collection\ITree $options) {
        self::purgeAll($options);
    }

    public static function purgeAll(core\collection\ITree $options) {
        if(!self::isLoadable()) {
            return;
        }

        $path1 = df\Launchpad::$application->getSharedStoragePath().'/cache/';
        $path2 = df\Launchpad::$application->getLocalStoragePath().'/cache/';

        core\fs\Dir::deleteContents($path1);
        core\fs\Dir::deleteContents($path2);
    }

    public static function prune(core\collection\ITree $options) {
        $paths = [
            df\Launchpad::$application->getSharedStoragePath().'/cache',
            df\Launchpad::$application->getLocalStoragePath().'/cache'
        ];

        clearstatcache();
        $stamp = core\time\Date::factory('-'.self::PRUNE_LIFETIME)->toTimestamp();
        $output = 0;

        foreach($paths as $basePath) {
            $baseDir = core\fs\Dir::factory($path);

            foreach($baseDir->scanDirs() as $dirName => $dir) {
                foreach($dir->scanFiles() as $fileName => $file) {
                    if($file->getLastModified() < $stamp) {
                        $file->delete();
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

    public static function isLoadable() {
        return true;
    }

    public function __construct(core\cache\ICache $cache, $lifeTime, core\collection\ITree $options) {
        $this->_cache = $cache;
        $this->_lifeTime = $lifeTime;
        
        if($cache->isCacheDistributed()) {
            $path = df\Launchpad::$application->getSharedStoragePath();
        } else {
            $path = df\Launchpad::$application->getLocalStoragePath();
        }

        $path .= '/cache/'.core\string\Manipulator::formatFileName($cache->getCacheId());
        $this->_dir = core\fs\Dir::create($path);
    }

    public function getConnectionDescription() {
        return $this->_dir->getLocationPath();
    }

    public function getStats() {
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

    public function setLifeTime($lifeTime) {
        $this->_lifeTime = $lifeTime;
        return $this;
    }

    public function getLifeTime() {
        return $this->_lifeTime;
    }

    public function shouldSerialize($flag=null) {
        if($flag !== null) {
            $this->_serialize = (bool)$flag;
            return $this;
        }

        return $this->_serialize;
    }

    public function set($key, $value) {
        if($this->_serialize) {
            $value = serialize($value);
        }

        $key = $this->_normalizeKey($key);
        $file = $this->_dir->createFile('cache-'.$key, $value);

        return true;
    }
    
    public function get($key, $default=null) {
        $key = $this->_normalizeKey($key);
        $file = $this->_dir->getFile('cache-'.$key);
        clearstatcache();

        if(!$file->exists()) {
            return $default;
        }

        if(!$file->isRecent($this->_lifeTime)) {
            $file->unlink();
            return $default;
        }
        
        $output = $file->getContents();

        if($this->_serialize) {
            try {
                $output = unserialize($output);
            } catch(\Exception $e) {
                $file->unlink();
                return $default;
            }
        }

        return $output;
    }
    
    public function has($key) {
        $key = $this->_normalizeKey($key);
        $file = $this->_dir->getFile('cache-'.$key);
        clearstatcache();

        if(!$file->exists()) {
            return false;
        }

        if(!$file->isRecent($this->_lifeTime)) {
            $file->unlink();
            return false;
        }

        return true;
    }
    
    public function remove($key) {
        $key = $this->_normalizeKey($key);
        $this->_dir->getFile('cache-'.$key)->unlink();

        return true;
    }
    
    public function clear() {
        $this->_dir->emptyOut();
        return true;
    }

    public function clearBegins($key) {
        $key = $this->_normalizeKey($key);
        $length = strlen($key);

        foreach($this->_dir->scanFiles() as $name => $file) {
            if(substr($name, 6, $length) == $key) {
                $file->unlink();
            }
        }

        return true;
    }

    public function clearMatches($regex) {
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

    public function getKeys() {
        $output = [];

        foreach($this->_dir->scanFiles() as $name => $file) {
            $output[] = substr($name, 6);
        }

        return $output;
    }

    public function getCreationTime($key) {
        $key = $this->_normalizeKey($key);
        $file = $this->_dir->getFile('cache-'.$key);
        clearstatcache();

        if(!$file->exists()) {
            return null;
        }

        return $file->getLastModified();
    }

    public function getDirectFilePath($key) {
        if($file = $this->getDirectFile($key)) {
            return $file->getPath();
        }
    }

    public function getDirectFileSize($key) {
        if($file = $this->getDirectFile($key)) {
            return $file->getSize();
        }
    }

    public function getDirectFile($key) {
        $key = $this->_normalizeKey($key);
        $file = $this->_dir->getFile('cache-'.$key);
        clearstatcache();

        if(!$file->exists()) {
            return null;
        }

        if(!$file->isRecent($this->_lifeTime)) {
            $file->unlink();
            return null;
        }

        return $file;
    }

    public function getDirectFileList() {
        $output = [];

        foreach($this->_dir->scanFiles() as $name => $file) {
            $key = substr($name, 6);
            $output[$key] = $file;
        }

        return $output;
    }

    protected static function _normalizeKey($key) {
        return core\string\Manipulator::formatFileName($key);
    }
}