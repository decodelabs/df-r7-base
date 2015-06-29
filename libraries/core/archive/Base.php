<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\archive;

use df;
use df\core;

abstract class Base implements IArchive {
    
    public static function extract($file, $destination=null, $flattenRoot=false) {
        if(preg_match('/\.zip$/i', $file)) {
            $type = 'Zip';
        } else if(preg_match('/\.tar(\.(gz|bz2))?$/i', $file)) {
            $type = 'Tar';
        } else if(preg_match('/\.gz$/i', $file)) {
            $type = 'Gz';
        } else if(preg_match('/\.rar$/i', $file)) {
            $type = 'Rar';
        } else if(preg_match('/\.bz2$/i', $file)) {
            $type = 'Bz2';
        } else {
            throw new RuntimeException('Unable to work out type of archive: '.$file);
        }

        return self::factory($type)->extractFile($file, $destination, $flattenRoot);
    }

    public static function factory($type) {
        $class = 'df\\core\\archive\\'.ucfirst($type);

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

    public function extractFile($file, $destDir=null, $flattenRoot=false) {
        throw new LogicException($this->getType().' type archives cannot handle file and folder compression');
    }

    public function decompressFile($file, $destFile=null) {
        throw new LogicException($this->getType().' type archives cannot handle file and folder compression');
    }

    public function compressString($string) {
        throw new LogicException($this->getType().' type archives cannot handle string compression');
    }

    public function decompressString($string) {
        throw new LogicException($this->getType().' type archives cannot handle string compression');
    }

    protected function _normalizeExtractDestination(&$file, $destination) {
        $file = str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, realpath($file));

        if(!is_file($file)) {
            throw new RuntimeException(
                'Source archive could not be found: '.$file
            );
        }

        if($destination === null) {
            $destination = dirname($file);
        }

        core\fs\Dir::create($destination);
        

        return $destination;
    }

    protected function _normalizeDecompressDestination(&$file, $destFile, $extension) {
        $file = str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, realpath($file));

        if($destFile !== null) {
            $destFile = str_replace('\\', '/', $destFile);

            if(false === strpos($destFile, '/')) {
                $destFile = dirname($file).'/'.$destFile;
            }
        }

        if(!is_file($file)) {
            throw new RuntimeException(
                'Source archive could not be found: '.$file
            );
        }

        if($destFile === null) {
            $destFile = dirname($file).'/'.$this->_getDecompressFileName($file, $extension);
        }

        core\fs\Dir::create(dirname($destFile));
        return $destFile;
    }

    protected function _getDecompressFileName($file, $extension) {
        $fileName = basename($file);
        $extLength = 1 + strlen($extension);

        if(strtolower(substr($fileName, -$extLength)) == '.'.$extension) {
            $fileName = substr($fileName, 0, -$extLength);
        } else {
            $fileName .= '-extract';
        }

        return $fileName;
    }

    protected function _flattenRoot($destination) {
        $dir = core\fs\Dir::factory($destination);
        $dirName = null;

        foreach($dir->scan() as $item) {
            if($item instanceof core\fs\IFile) {
                return;
            }

            if($dirName !== null) {
                return;
            }

            $dirName = $item->getName();
        }

        $name = $dir->getName();
        $dir->renameTo($name.'-'.time());
        $dir->getDir($dirName)->moveTo($dir->getParent(), $name);
        $dir->unlink();
    }
}