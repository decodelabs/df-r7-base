<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io;

use df;
use df\core;

class Util implements IUtil {
    
    public static function readFileExclusive($path) {
        if(!$fp = fopen($path, 'rb')) {
            throw new \Exception(
                'File could not be opened for reading'
            );
        }

        flock($fp, LOCK_SH);

        $output = '';

        while(!feof($fp)) {
            $output .= fread($fp, 8192);
        }

        flock($fp, LOCK_UN);
        fclose($fp);

        return $output;
    }

    public static function writeFileExclusive($path, $data) {
        self::ensureDirExists(dirname($path));

        if(!$fp = fopen($path, 'wb')) {
            throw new \Exception(
                'File could not be opened for writing'
            );
        }

        flock($fp, LOCK_EX);
        fwrite($fp, $data);
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }

    public static function copyFile($source, $destination) {
        if(!is_file($source)) {
            throw new \Exception(
                'Source file does not exist'
            );
        }

        $dir = dirname($destination);
        self::ensureDirExists(dirname($destination));

        return copy($source, $destination);
    }

    public static function deleteFile($path) {
        if(file_exists($path)) {
            return @unlink($path);
        }

        return true;
    }

    public static function countFilesIn($path) {
        if(!is_dir($path)) {
            return 0;
        }

        $output = 0;

        foreach(new \DirectoryIterator($path) as $item) {
            if($item->isFile()) {
                $output++;
            }
        }

        return $output;
    }

    public static function countDirsIn($path) {
        if(!is_dir($path)) {
            return 0;
        }

        $output = 0;

        foreach(new \DirectoryIterator($path) as $item) {
            if($item->isDir()) {
                $output++;
            }
        }

        return $output;
    }

    public static function listFilesIn($path) {
        if(!is_dir($path)) {
            return [];
        }

        $output = [];

        foreach(new \DirectoryIterator($path) as $item) {
            if($item->isFile()) {
                $output[] = $item->getFilename();
            }
        }

        return $output;
    }

    public static function listDirsIn($path) {
        if(!is_dir($path)) {
            return [];
        }

        $output = [];

        foreach(new \DirectoryIterator($path) as $item) {
            if($item->isDir()) {
                $output[] = $item->getFilename();
            }
        }

        return $output;
    }

    public static function copyDir($source, $destination, $merge=false) {
        if(!is_dir($source)) {
            throw new \Exception(
                'Source directory does not exist'
            );
        }
        
        if(is_dir($destination)) {
            if(!$merge) {
                throw new \Exception(
                    'Destination directory already exists'
                );
            }
        } else {
            try {
                $perms = octdec(substr(decoct(fileperms($source)), 1));
            } catch(\Exception $e) {
                $perms = 0777;
            }

            mkdir($destination, $perms, true);
        }
        
        foreach(scandir($source) as $entry) {
            if($entry == '.' || $entry == '..') {
                continue;
            }
            
            if(is_dir($source.'/'.$entry)) {
                self::copyDir($source.'/'.$entry, $destination.'/'.$entry, $merge);
            } else {
                copy($source.'/'.$entry, $destination.'/'.$entry);
            }
        }

        return true;
    }
    
    public static function copyDirInto($source, $destination) {
        if(!is_dir($source)) {
            throw new \Exception(
                'Source directory does not exist'
            );
        }
        
        if(!is_dir($destination)) {
            throw new \Exception(
                'Destination directory does not exists'
            );
        }
        
        foreach(scandir($source) as $entry) {
            if($entry == '.' || $entry == '..') {
                continue;
            }
            
            if(is_dir($source.'/'.$entry)) {
                if(!is_dir($destination.'/'.$entry)) {
                    try {
                        $perms = octdec(substr(decoct(fileperms($source.'/'.$entry)), 1));
                    } catch(\Exception $e) {
                        $perms = 0777;
                    }

                    mkdir($destination.'/'.$entry, $perms, false);
                }
                
                self::copyDirInto($source.'/'.$entry, $destination.'/'.$entry);
            } else {
                copy($source.'/'.$entry, $destination.'/'.$entry);
            }
        }
    }

    public static function ensureDirExists($path, $perms=0777) {
        if(!is_dir($path)) {
            $umask = umask(0);
            $result = !mkdir($path, $perms, true);
            umask($umask);
            
            if($result) {
                throw new \Exception(
                    'Directory is not writable'
                );
            }
        }

        return true;
    }
    
    public static function isDirEmpty($path) {
        if(!is_dir($path)) {
            if(!file_exists($path)) {
                return true;
            } 

            throw new \Exception(
                'Path is not a directory: '.$path
            );
        }
        
        return !(($files = @scandir($path)) && (count($files) > 2));
    }
    
    public static function deleteDir($path) {
        if(!self::emptyDir($path)) {
            return false;
        }

        rmdir($path);
        return true;
    }
    
    public static function emptyDir($path) {
        if(!is_dir($path)) {
            return;
        }

        foreach(new \DirectoryIterator($path) as $item) {
            if($item->isDot()) {
                continue;
            }
            
            if($item->isDir()) {
                if(!self::deleteDir($item->getPathname())) {
                    return false;
                }
            } else {
                if(!@unlink($item->getPathname())) {
                    return false;
                }
            }
        }
        
        return true;
    }



    public static function chmod($path, $mode, $recursive=false) {
        if(!file_exists($path)) {
            return false;
        }

        chmod($path, $mode);

        if($recursive && is_dir($path)) {
            foreach(new \DirectoryIterator($path) as $item) {
                if($item->isDot()) {
                    continue;
                }

                self::chmod($item->getPathname(), $mode, $recursive);
            }
        }

        return true;
    }


    public static function getBaseName($path) {
        return basename($path);
    }

    public static function getFileName($path) {
        $base = basename($path);
        $parts = explode('.', $base, 2);
        return array_shift($parts);
    }

    public static function getExtension($path) {
        $base = basename($path);
        $parts = explode('.', $base, 2);
        return array_pop($parts);
    }

    public static function stripLocationFromFilePath($path) {
        if(!df\Launchpad::$loader) {
            return $path;
        }
        
        $locations = df\Launchpad::$loader->getLocations();
        $locations['app'] = df\Launchpad::$applicationPath;
        
        foreach($locations as $key => $match) {
            if(substr($path, 0, $len = strlen($match)) == $match) {
                $path = $key.'://'.substr(str_replace('\\', '/', $path), $len + 1);
                break;
            }
        }
        
        return $path;
    }
}
