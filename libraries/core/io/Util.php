<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io;

use df;
use df\core;

class Util {
    
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

            mkdir($destination, $perms, false);
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
    
    public static function isDirEmpty($path) {
        if(!is_dir($path)) {
            throw new \Exception(
                'Path is not a directory'
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
                if(!unlink($item->getPathname())) {
                    return false;
                }
            }
        }
        
        return true;
    }
}
