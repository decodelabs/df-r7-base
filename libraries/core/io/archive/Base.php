<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\io\archive;

use df;
use df\core;

abstract class Base implements IArchive {
    
    public static function extract($file, $destination=null, $flattenRoot=false) {
        if(preg_match('/\.zip$/i', $file)) {
            $type = 'Zip';
        } else if(preg_match('/\.tar(\.gz)?$/i', $file)) {
            $type = 'Tar';
        } else if(preg_match('/\.rar$/i', $file)) {
            $type = 'Rar';
        } else if(preg_match('/\.bz2$/i', $file)) {
            $type = 'Bz2';
        } else {
            throw new RuntimeException('Unable to work out type of archive: '.$file);
        }

        return self::factory($type)->decompressFile($file, $destination, $flattenRoot);
    }

    public static function factory($type) {
        $class = 'df\\core\\io\\archive\\'.ucfirst($type);

        if(!class_exists($class)) {
            throw new LogicException('Archive type '.$type.' is not supported');
        }

        return new $class();
    }

    public function __construct() {}

    public function getType() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }

    public function decompressFile($file, $destination=null, $flattenRoot=false) {
        throw new LogicException($this->getType().' type archives cannot handle file and folder compression');
    }

    public function decompressString($string) {
        throw new LogicException($this->getType().' type archives cannot handle string compression');
    }

    protected function _normalizeExtractDestination(&$file, $destination) {
        if($destination === null) {
            $destination = dirname($file);
        }

        core\io\Util::ensureDirExists($destination);
        $file = str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, realpath($file));

        if(!is_file($file)) {
            throw new RuntimeException(
                'Source archive could not be found: '.$file
            );
        }

        return $destination;
    }

    protected function _flattenRoot($destination) {
        $dirName = null;

        foreach(new \DirectoryIterator($destination) as $item) {
            if($item->isDot()) {
                continue;
            }

            if($item->isFile()) {
                return;
            }

            if($item->isDir()) {
                if($dirName !== null) {
                    return;
                }

                $dirName = $item->getFilename();
            }
        }

        $newName = basename($destination).'-'.time();
        core\io\Util::renameDir($destination, $newName);
        core\io\Util::moveDir(
            dirname($destination).'/'.$newName.'/'.$dirName, 
            dirname($destination), 
            basename($destination)
        );
        core\io\Util::deleteDir(dirname($destination).'/'.$newName);
    }
}