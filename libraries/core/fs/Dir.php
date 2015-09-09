<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\fs;

use df;
use df\core;

class Dir implements IDirectory, core\IDumpable {
    
    use TNode;

    protected $_path;

// Static
    public static function getGlobalCachePath() {
        return '/tmp/decode-framework';
        //return sys_get_temp_dir().'decode-framework';
    }

    public static function stripPathLocation($path) {
        if(!df\Launchpad::$loader) {
            return $path;
        }
        
        $locations = df\Launchpad::$loader->getLocations();
        $locations['app'] = df\Launchpad::$applicationPath;
        
        foreach($locations as $key => $match) {
            if(substr($path, 0, $len = strlen($match)) == $match) {
                $innerPath = substr(str_replace('\\', '/', $path), $len + 1);

                if(df\Launchpad::IS_COMPILED && $key == 'root') {
                    $parts = explode('/', $innerPath);
                    array_shift($parts);
                    $innerPath = implode('/', $parts);
                }

                $path = $key.'://'.$innerPath;
                break;
            }
        }
        
        return $path;
    }
    
    public static function create($path, $perms=null) {
        return self::factory($path)->ensureExists($perms);
    }

    public static function createTemp() {
        return new self(
            sys_get_temp_dir().'decode-framework/temp/'. 
            core\string\Uuid::comb()
        );
    }

    public static function createUploadTemp($path=null) {
        if($path === null) {
            $path = df\Launchpad::$isDistributed ?
                df\Launchpad::$application->getSharedStoragePath() :
                df\Launchpad::$application->getLocalStoragePath();

            $path .= '/upload/'.core\string\Uuid::v1();
        }

        return self::create($path);
    }

    public static function purgeUploadTemp() {
        $path = df\Launchpad::$isDistributed ?
            df\Launchpad::$application->getSharedStoragePath() :
            df\Launchpad::$application->getLocalStoragePath();

        $path .= '/upload/';


        foreach((new self($path))->scanDirs() as $name => $dir) {
            try {
                $guid = core\string\Uuid::factory($name);
            } catch(\Exception $e) {
                continue;
            }

            $time = $guid->getTime();

            if(!$time) {
                continue;
            }

            $date = core\time\Date::factory((int)$time);

            if($date->lt('-2 days')) {
                $dir->unlink();
            }
        }
    }


    public static function isDirRecent($path, $timeout) {
        return self::factory($path)->isRecent($timeout);
    }

    public static function isDirEmpty($path) {
        return self::factory($path)->isEmpty();
    }

    public static function setPermissionsOn($path, $mode) {
        return self::factory($path)->setPermissions($mode);
    }

    public static function setOwnerOn($path, $owner) {
        return self::factory($path)->setOwner($owner);
    }

    public static function setGroupOn($path, $group) {
        return self::factory($path)->setGroup($group);
    }



    public static function copy($from, $to) {
        return self::factory($from)->copyTo($to);
    }

    public static function merge($from, $to) {
        return self::factory($from)->mergeInto($to);
    }

    public static function rename($from, $to) {
        return self::factory($from)->renameTo($to);
    }

    public static function move($from, $to, $newName=null) {
        return self::factory($from)->moveTo($to, $newName);
    }

    public static function delete($path) {
        return self::factory($path)->unlink();
    }

    public static function deleteContents($path) {
        return self::factory($path)->emptyOut();
    }


// Init
    public static function factory($path) {
        if($path instanceof IDirectory) {
            return $path;
        }

        return new self($path);
    }

    public function __construct($path) {
        $this->_path = rtrim($path, '/');
    }

    public function getPath() {
        return $this->_path;
    }

    public function exists() {
        return is_dir($this->_path);
    }

    public function ensureExists($perms=null) {
        if(!is_dir($this->_path)) {
            if($perms === null) {
                $perms = 0777;
            }

            $umask = umask(0);

            try {
                $result = !mkdir($this->_path, $perms, true);
            } catch(\ErrorException $e) {
                if(!is_dir($this->_path)) {
                    umask($umask);
                    throw $e;
                }

                $result = false;
            }

            umask($umask);
            
            if($result) {
                throw new \Exception(
                    'Directory is not writable'
                );
            }
        } else {
            if($perms !== null) {
                chmod($this->_path, $perms);
            }
        }

        return $this;
    }

    public function isEmpty() {
        if(!$this->exists()) {
            return true;
        }

        foreach(new \DirectoryIterator($this->_path) as $item) {
            if($item->isDot()) {
                continue;
            }

            if($item->isFile() || $item->isDir()) {
                return false;
            }
        }

        return true;
    }

    public function getLastModified() {
        return filemtime($this->_path);
    }



    public function setPermissions($mode, $recursive=false) {
        chmod($this->_path, $mode);

        if($recursive) {
            foreach($this->_scan(true, true) as $item) {
                if($item instanceof IDirectory) {
                    $item->setPermissions($mode, true);
                } else {
                    $item->setPermissions($mode);
                }
            }
        }

        return $this;
    }

    public function getPermissions() {
        return fileperms($this->_path);
    }

    public function setOwner($owner, $recursive=false) {
        chown($this->_path, $owner);

        if($recursive) {
            foreach($this->_scan(true, true) as $item) {
                if($item instanceof IDirectory) {
                    $item->setOwner($mode, true);
                } else {
                    $item->setOwner($mode);
                }
            }
        }

        return $this;
    }

    public function getOwner() {
        return fileowner($this->_path);
    }

    public function setGroup($group, $recursive=false) {
        chgrp($this->_path, $owner);

        if($recursive) {
            foreach($this->_scan(true, true) as $item) {
                if($item instanceof IDirectory) {
                    $item->setGroup($mode, true);
                } else {
                    $item->setGroup($mode);
                }
            }
        }

        return $this;
    }

    public function getGroup() {
        return filegroup($this->_path);
    }


    public function scan($filter=null) {
        return $this->_scan(true, true, $filter);
    }

    public function scanNames($filter=null) {
        return $this->_scan(true, true, $filter, null);
    }

    public function countContents($filter=null) {
        return $this->_countGenerator($this->_scan(true, true, $filter, false));
    }

    public function listContents($filter=null) {
        return $this->_listGenerator($this->_scan(true, true, $filter));
    }

    public function listNames($filter=null) {
        return $this->_listGenerator($this->_scan(true, true, $filter, null));
    }



    public function scanFiles($filter=null) {
        return $this->_scan(true, false, $filter);
    }

    public function scanFileNames($filter=null) {
        return $this->_scan(true, false, $filter, null);
    }

    public function countFiles($filter=null) {
        return $this->_countGenerator($this->_scan(true, false, $filter, false));
    }

    public function listFiles($filter=null) {
        return $this->_listGenerator($this->_scan(true, false, $filter));
    }

    public function listFileNames($filter=null) {
        return $this->_listGenerator($this->_scan(true, false, $filter, null));
    }



    public function scanDirs($filter=null) {
        return $this->_scan(false, true, $filter);
    }

    public function scanDirNames($filter=null) {
        return $this->_scan(false, true, $filter, null);
    }

    public function countDirs($filter=null) {
        return $this->_countGenerator($this->_scan(false, true, $filter, false));
    }

    public function listDirs($filter=null) {
        return $this->_listGenerator($this->_scan(false, true, $filter));
    }

    public function listDirNames($filter=null) {
        return $this->_listGenerator($this->_scan(false, true, $filter, null));
    }



    protected function _scan($files, $dirs, $filter=null, $wrap=true) {
        if(!$this->exists()) {
            return;
        }

        if($filter) {
            $filter = core\lang\Callback::factory($filter);
        }

        foreach(new \DirectoryIterator($this->_path) as $item) {
            if($item->isDot()) {
                continue;
            } else if($item->isDir()) {
                if(!$dirs) {
                    continue;
                }

                $output = $item->getPathname();

                if($wrap) {
                    $output = new self($output);
                }
            } else if($item->isFile()) {
                if(!$files) {
                    continue;
                }

                $output = $item->getPathname();

                if($wrap) {
                    $output = new File($output);
                }
            } else {
                // link?
                continue;
            }

            $key = $item->getFilename();

            if($filter && !$filter->invoke($key, $output)) {
                continue;
            }

            if($wrap === null) {
                yield $key;
            } else {
                yield $key => $output;
            }
        }
    }

    public function scanRecursive($filter=null) {
        return $this->_scanRecursive(true, true, $filter);
    }

    public function scanNamesRecursive($filter=null) {
        return $this->_scanRecursive(true, true, $filter, null);
    }

    public function countContentsRecursive($filter=null) {
        return $this->_countGenerator($this->_scanRecursive(true, true, $filter, false));
    }

    public function listContentsRecursive($filter=null) {
        return $this->_listGenerator($this->_scanRecursive(true, true, $filter));
    }

    public function listNamesRecursive($filter=null) {
        return $this->_listGenerator($this->_scanRecursive(true, true, $filter, null));
    }



    public function scanFilesRecursive($filter=null) {
        return $this->_scanRecursive(true, false, $filter);
    }

    public function scanFileNamesRecursive($filter=null) {
        return $this->_scanRecursive(true, false, $filter, null);
    }

    public function countFilesRecursive($filter=null) {
        return $this->_countGenerator($this->_scanRecursive(true, false, $filter, false));
    }

    public function listFilesRecursive($filter=null) {
        return $this->_listGenerator($this->_scanRecursive(true, false, $filter));
    }

    public function listFileNamesRecursive($filter=null) {
        return $this->_listGenerator($this->_scanRecursive(true, false, $filter, null));
    }



    public function scanDirsRecursive($filter=null) {
        return $this->_scanRecursive(false, true, $filter);
    }

    public function scanDirNamesRecursive($filter=null) {
        return $this->_scanRecursive(false, true, $filter, null);
    }

    public function countDirsRecursive($filter=null) {
        return $this->_countGenerator($this->_scanRecursive(false, true, $filter, false));
    }

    public function listDirsRecursive($filter=null) {
        return $this->_listGenerator($this->_scanRecursive(false, true, $filter));
    }

    public function listDirNamesRecursive($filter=null) {
        return $this->_listGenerator($this->_scanRecursive(false, true, $filter, null));
    }



    protected function _scanRecursive($files, $dirs, $filter=null, $wrap=true) {
        if(!$this->exists()) {
            return;
        }

        if($filter) {
            $filter = core\lang\Callback::factory($filter);
        }

        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $this->_path, 
                \FilesystemIterator::KEY_AS_PATHNAME | 
                \FilesystemIterator::CURRENT_AS_SELF | 
                \FilesystemIterator::SKIP_DOTS
            ), 
            $dirs ?
                \RecursiveIteratorIterator::SELF_FIRST :
                \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach($it as $item) {
            if($item->isDir()) {
                if(!$dirs) {
                    continue;
                }

                $output = $item->getPathname();

                if($wrap) {
                    $output = new self($output);
                }
            } else if($item->isFile()) {
                if(!$files) {
                    continue;
                }

                $output = $item->getPathname();

                if($wrap) {
                    $output = new File($output);
                }
            }

            $key = $item->getSubPathname();

            if($filter && !$filter->invoke($key, $output)) {
                continue;
            }

            if($wrap === null) {
                yield $key;
            } else {
                yield $key => $output;
            }
        }
    }


    protected function _countGenerator($generator) {
        $output = 0;

        foreach($generator as $item) {
            $output++;
        }

        return $output;
    }

    protected function _listGenerator($generator) {
        $output = [];

        foreach($generator as $name => $item) {
            $output[$name] = $item;
        }

        return $output;
    }



    public function getParent() {
        if(($path = dirname($this->_path)) == $this->_path) {
            return null;
        }

        return new self($path);
    }

    public function getChild($name) {
        $path = $this->_path.'/'.ltrim($name, '/');

        if(is_dir($path)) {
            return new self($path);
        } else if(is_file($path)) {
            return new File($path);
        }

        throw new RuntimeException('Child '.$name.' does not exist');
    }

    public function deleteChild($name) {
        return $this->getChild($name)->unlink();
    }

    public function createDir($path) {
        return self::create($this->_path.'/'.ltrim($path, '/'));
    }

    public function hasDir($name) {
        return $this->getDir($name)->exists();
    }

    public function getDir($name) {
        return new self($this->_path.'/'.ltrim($name, '/'));
    }

    public function deleteDir($name) {
        return $this->getDir($name)->unlink();
    }

    public function createFile($name, $content, $mode=null) {
        return File::create($this->_path.'/'.ltrim($name, '/'), $content, $mode);
    }

    public function newFile($name, $mode=Mode::READ_WRITE_NEW) {
        return $this->getFile($name)->open($mode);
    }

    public function hasFile($name) {
        return $this->getFile($name)->exists();
    }

    public function getFile($name) {
        return new File($this->_path.'/'.ltrim($name, '/'));
    }

    public function deleteFile($name) {
        return $this->getFile($name)->unlink();
    }


    public function copyTo($destination) {
        $destination = self::factory($destination);
        
        if($destination->exists()) {
            throw new \Exception(
                'Destination directory already exists '.$destination->getPath()
            );
        }

        return $this->mergeInto($destination);
    }

    public function mergeInto($destination) {
        if(!is_dir($this->_path)) {
            throw new \Exception(
                'Source directory does not exist'
            );
        }
        
        $destination = self::create($destination, $this->getPermissions());

        foreach($this->_scanRecursive(true, true) as $subPath => $item) {
            if($item instanceof IDirectory) {
                $destination->createDir($subPath, $item->getPermissions());
            } else {
                $item->copyTo($destination->getPath().'/'.$subPath)
                    ->setPermissions($item->getPermissions());
            }
        }

        return $this;
    }

    public function renameTo($newName) {
        return $this->moveTo(dirname($this->_path), $newName);
    }

    public function moveTo($destination, $newName=null) {
        if(!is_dir($this->_path)) {
            throw new \Exception(
                'Source directory does not exist'
            );
        }

        if($newName === null) {
            $newName = basename($this->_path);
        }

        $destination = self::factory($destination);
        $target = $destination->getDir($newName);

        if($target->exists()) {
            throw new \Exception(
                'Destination directory already exists'
            );
        }

        $destination->ensureExists();

        rename($this->_path, $target->getPath());
        $this->_path = $target->getPath();

        return $this;
    }

    public function unlink() {
        if(!is_dir($this->_path)) {
            return $this;
        }

        foreach($this->_scan(true, true) as $item) {
            $item->unlink();
        }

        rmdir($this->_path);
        return $this;
    }

    public function emptyOut() {
        if(!is_dir($this->_path)) {
            return $this;
        }

        foreach($this->_scan(true, true) as $item) {
            $item->unlink();
        }

        return $this;
    }

// Dump
    public function getDumpProperties() {
        return $this->_path;
    }
}