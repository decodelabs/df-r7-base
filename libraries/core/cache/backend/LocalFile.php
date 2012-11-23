<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\cache\backend;

use df;
use df\core;
    
class LocalFile implements core\cache\IDirectFileBackend {

    protected $_lifeTime;
    protected $_cache;
    protected $_path;
    protected $_serialize = true;

    public static function isLoadable() {
        return true;
    }

    public function __construct(core\cache\ICache $cache, $lifeTime, core\collection\ITree $options) {
        $this->_cache = $cache;
        $this->_lifeTime = $lifeTime;
        $this->_path = $cache->getApplication()->getLocalDataStoragePath().'/cache/'.core\string\Manipulator::formatFileName($cache->getCacheId());

        core\io\Util::ensureDirExists($this->_path);
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

    protected function _normalizeKey($key) {
        return core\string\Manipulator::formatFileName($key);
    }
}