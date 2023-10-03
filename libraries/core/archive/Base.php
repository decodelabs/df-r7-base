<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\archive;

use DecodeLabs\Atlas;
use DecodeLabs\Exceptional;

abstract class Base implements IArchive
{
    public static function extract($file, $destination = null, $flattenRoot = false)
    {
        $file = (string)$file;

        if (preg_match('/\.zip$/i', $file)) {
            $type = 'Zip';
        } elseif (preg_match('/\.tar(\.(gz|bz2))?$/i', $file)) {
            $type = 'Tar';
        } elseif (preg_match('/\.gz$/i', $file)) {
            $type = 'Gz';
        } elseif (preg_match('/\.rar$/i', $file)) {
            $type = 'Rar';
        } elseif (preg_match('/\.bz2$/i', $file)) {
            $type = 'Bz2';
        } else {
            throw Exceptional::Runtime(
                'Unable to detect type of archive: ' . $file
            );
        }

        return self::factory($type)->extractFile($file, $destination, $flattenRoot);
    }

    public static function factory(string $type): IArchive
    {
        $class = 'df\\core\\archive\\' . ucfirst($type);

        if (!class_exists($class)) {
            throw Exceptional::Unsupported(
                'Archive type ' . $type . ' is not supported'
            );
        }

        return new $class();
    }

    public function getType(): string
    {
        return (new \ReflectionObject($this))->getShortName();
    }

    public function extractFile(string $file, string $destDir = null, bool $flattenRoot = false): string
    {
        throw Exceptional::Unsupported(
            $this->getType() . ' type archives cannot handle file and folder compression'
        );
    }

    public function decompressFile(string $file, string $destFile = null): string
    {
        throw Exceptional::Unsupported(
            $this->getType() . ' type archives cannot handle file and folder compression'
        );
    }

    public function compressString(string $string): string
    {
        throw Exceptional::Unsupported(
            $this->getType() . ' type archives cannot handle string compression'
        );
    }

    public function decompressString(string $string): string
    {
        throw Exceptional::Unsupported(
            $this->getType() . ' type archives cannot handle string compression'
        );
    }

    protected function _normalizeExtractDestination(string &$file, ?string $destination): string
    {
        $file = str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, (string)realpath($file));

        if (!is_file($file)) {
            throw Exceptional::NotFound(
                'Source archive could not be found: ' . $file
            );
        }

        if (empty($destination)) {
            $destination = dirname($file);
        }

        Atlas::createDir($destination);
        return $destination;
    }

    protected function _normalizeDecompressDestination(string &$file, ?string $destFile, string $extension): string
    {
        $file = str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, (string)realpath($file));

        if ($destFile !== null) {
            $destFile = str_replace('\\', '/', $destFile);

            if (false === strpos($destFile, '/')) {
                $destFile = dirname($file) . '/' . $destFile;
            }
        }

        if (!is_file($file)) {
            throw Exceptional::NotFound(
                'Source archive could not be found: ' . $file
            );
        }

        if ($destFile === null) {
            $destFile = dirname($file) . '/' . $this->_getDecompressFileName($file, $extension);
        }

        Atlas::createDir(dirname($destFile));
        return $destFile;
    }

    protected function _getDecompressFileName(string $file, string $extension): string
    {
        $fileName = basename($file);
        $extLength = 1 + strlen($extension);

        if (strtolower(substr($fileName, -$extLength)) == '.' . $extension) {
            $fileName = substr($fileName, 0, -$extLength);
        } else {
            $fileName .= '-extract';
        }

        return $fileName;
    }

    protected function _flattenRoot(string $destination): void
    {
        $dir = Atlas::dir($destination);
        $dirName = null;

        foreach ($dir->scan() as $item) {
            if ($item->isFile()) {
                return;
            }

            if ($dirName !== null) {
                return;
            }

            $dirName = $item->getName();
        }

        $name = $dir->getName();
        $dir->renameTo($name . '-' . time());
        $dir->getDir($dirName)->moveTo($dir->getParent(), $name);
        $dir->delete();
    }
}
