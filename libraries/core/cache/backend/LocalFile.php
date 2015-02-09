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
    protected $_path;
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

        core\io\Util::emptyDir($path1);
        core\io\Util::emptyDir($path2);
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
            foreach(core\io\Util::listDirsIn($basePath) as $name) {
                $path = $basePath.'/'.$name;
                
                foreach(core\io\Util::listFilesIn($path) as $key) {
                    $filePath = $path.'/'.$key;
                    $mTime = filemtime($filePath);

                    if($mTime < $stamp) {
                        core\io\Util::deleteFile($filePath);
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
            $this->_path = df\Launchpad::$application->getSharedStoragePath();
        } else {
            $this->_path = df\Launchpad::$application->getLocalStoragePath();
        }

        $this->_path .= '/cache/'.core\string\Manipulator::formatFileName($cache->getCacheId());

        core\io\Util::ensureDirExists($this->_path);
    }

    public function getConnectionDescription() {
        return core\io\Util::stripLocationFromFilePath($this->_path);
    }

    public function getStats() {
        $count = 0;
        $size = 0;

        foreach(core\io\Util::listFilesIn($this->_path) as $name) {
            $count++;
            $size += filesize($this->_path.'/'.$name);
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
        $key = $this->_normalizeKey($key);
        $filePath = $this->_path.'/cache-'.$key;

        if($this->_serialize) {
            $value = serialize($value);
        }

        core\io\Util::writeFileExclusive($filePath, $value);

        return true;
    }
    
    public function get($key, $default=null) {
        $key = $this->_normalizeKey($key);
        $filePath = $this->_path.'/cache-'.$key;
        clearstatcache();

        if(!file_exists($filePath)) {
            return $default;
        }

        $mTime = filemtime($filePath);

        if(time() - $mTime > $this->_lifeTime) {
            core\io\Util::deleteFile($filePath);
            return $default;
        }
        
        $output = core\io\Util::readFileExclusive($filePath);

        if($this->_serialize) {
            try {
                $output = unserialize($output);
            } catch(\Exception $e) {
                core\io\Util::deleteFile($filePath);
                return $default;
            }
        }

        return $output;
    }
    
    public function has($key) {
        $key = $this->_normalizeKey($key);
        $filePath = $this->_path.'/cache-'.$key;
        clearstatcache();

        if(!file_exists($filePath)) {
            return false;
        }

        $mTime = filemtime($filePath);

        if(time() - $mTime > $this->_lifeTime) {
            core\io\Util::deleteFile($filePath);
            return false;
        }

        return true;
    }
    
    public function remove($key) {
        $key = $this->_normalizeKey($key);
        $filePath = $this->_path.'/cache-'.$key;
        core\io\Util::deleteFile($filePath);

        return true;
    }
    
    public function clear() {
        core\io\Util::emptyDir($this->_path);
        return true;
    }

    public function clearBegins($key) {
        $key = $this->_normalizeKey($key);
        $length = strlen($key);

        foreach(core\io\Util::listFilesIn($this->_path) as $name) {
            if(substr($name, 6, $length) == $key) {
                core\io\Util::deleteFile($this->_path.'/cache-'.$name);
            }
        }

        return true;
    }

    public function clearMatches($regex) {
        foreach(core\io\Util::listFilesIn($this->_path) as $name) {
            if(preg_match($regex, substr($name, 6))) {
                core\io\Util::deleteFile($this->_path.'/cache-'.$name);
            }
        }

        return true;
    }

    public function count() {
        return core\io\Util::countFilesIn($this->_path);
    }

    public function getKeys() {
        $output = [];

        foreach(core\io\Util::listFilesIn($this->_path) as $key) {
            $output[] = substr($key, 6);
        }

        return $output;
    }

    public function getCreationTime($key) {
        $key = $this->_normalizeKey($key);
        $filePath = $this->_path.'/cache-'.$key;
        clearstatcache();

        if(file_exists($filePath)) {
            return filemtime($filePath);
        }

        return null;
    }

    public function getDirectFilePath($key) {
        $key = $this->_normalizeKey($key);
        $filePath = $this->_path.'/cache-'.$key;
        clearstatcache();

        if(!file_exists($filePath)) {
            return null;
        }

        $mTime = filemtime($filePath);

        if(time() - $mTime > $this->_lifeTime) {
            core\io\Util::deleteFile($filePath);
            return null;
        }

        return $filePath;
    }

    public function getDirectFileSize($key) {
        if($path = $this->getDirectFilePath($key)) {
            return filesize($path);
        }

        return null;
    }

    public function getDirectFile($key) {
        if(null === ($path = $this->getDirectFilePath($key))) {
            return null;
        }

        return new core\io\LocalFilePointer($path);
    }

    public function getDirectFileList() {
        $output = [];

        foreach(core\io\Util::listFilesIn($this->_path) as $fileName) {
            $key = substr($fileName, 6);
            $output[$key] = new core\io\LocalFilePointer($this->_path.'/'.$fileName);
        }

        return $output;
    }

    protected static function _normalizeKey($key) {
        return core\string\Manipulator::formatFileName($key);
    }
}