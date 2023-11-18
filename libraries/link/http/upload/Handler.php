<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\link\http\upload;

use ArrayIterator;

use DecodeLabs\Atlas;
use DecodeLabs\Atlas\Dir;
use DecodeLabs\Genesis;
use DecodeLabs\Guidance;
use df\core;
use df\link;

class Handler implements link\http\IUploadHandler
{
    use core\lang\TAcceptTypeProcessor;

    protected $_files = [];
    protected $_extensions = [];
    protected $_maxSize;

    protected $_avScan = false;
    protected $_clamAvSocket = 'unix:///var/run/clamav/clamd.ctl';


    public static function createUploadTemp(?string $path = null): Dir
    {
        if ($path === null) {
            $path = Genesis::$hub->getSharedDataPath();
            $path .= '/upload/' . Guidance::createV1String();
        }

        return Atlas::createDir($path);
    }

    public static function purgeUploadTemp(): void
    {
        $path = Genesis::$hub->getSharedDataPath();
        $path .= '/upload/';


        foreach (Atlas::scanDirs($path) as $name => $dir) {
            try {
                $guid = Guidance::from($name);
            } catch (\Throwable $e) {
                continue;
            }

            $date = $guid->getDateTime();

            if (!$date) {
                continue;
            }

            $date = core\time\Date::factory($date->getTimestamp());

            if ($date->lt('-2 days')) {
                $dir->delete();
            }
        }
    }



    public function __construct()
    {
        if (empty($_FILES)) {
            return;
        }

        foreach ($_FILES as $key => $set) {
            if (is_array($set['name'])) {
                $sets = [];

                foreach ($set as $fileVar => $array) {
                    $array = $this->_flattenArray($array, $key);

                    foreach ($array as $field => $value) {
                        $sets[$field][$fileVar] = $value;
                    }
                }

                foreach ($sets as $key => $set) {
                    if (!empty($set['name'])) {
                        $this->_files[$key] = $set;
                    }
                }
            } else {
                if (!empty($set['name'])) {
                    $this->_files[$key] = $set;
                }
            }
        }

        $this->setMaxFileSize('256mb');
    }

    protected function _flattenArray(array $array, $currentKey = '')
    {
        $output = [];

        foreach ($array as $key => $var) {
            $thisKey = $currentKey . '[' . $key . ']';
            //$thisKey = $currentKey.'.'.$key;

            if (is_array($var)) {
                foreach ($this->_flattenArray($var, $thisKey) as $childKey => $value) {
                    $output[$childKey] = $value;
                }
            } else {
                $output[$thisKey] = $var;
            }
        }

        return $output;
    }

    public function setAllowedExtensions(array $extensions)
    {
        $this->_extensions = [];
        return $this->addAllowedExtensions($extensions);
    }

    public function addAllowedExtensions(array $extensions)
    {
        foreach ($extensions as $ext) {
            $this->_extensions[] = $this->_normalizeExtension($ext);
        }

        return $this;
    }

    public function getAllowedExtensions()
    {
        return $this->_extensions;
    }

    public function isExtensionAllowed($extension)
    {
        if (empty($this->_extensions)) {
            return true;
        }

        return in_array($this->_normalizeExtension($extension), $this->_extensions);
    }

    protected function _normalizeExtension($ext)
    {
        return ltrim(trim(strtolower((string)$ext)), '.');
    }



    public function setMaxFileSize($size)
    {
        $size = core\unit\FileSize::factory($size);
        $postMaxSize = core\unit\FileSize::factory(ini_get('post_max_size'));

        if ($postMaxSize->getValue() > -1 && $postMaxSize->getMegabytes() < $size->getMegabytes()) {
            $size = $postMaxSize;
        }

        $uploadMaxSize = core\unit\FileSize::factory(ini_get('upload_max_filesize'));

        if ($uploadMaxSize->getValue() > -1 && $uploadMaxSize->getMegabytes() < $size->getMegabytes()) {
            $size = $uploadMaxSize;
        }

        $this->_maxSize = $size;
        return $this;
    }

    public function getMaxFileSize()
    {
        return $this->_maxSize;
    }


    public function shouldAvScan(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_avScan = $flag;
            return $this;
        }

        return $this->_avScan;
    }

    public function setClamAvSocket(string $socket)
    {
        $this->_clamAvSocket = $socket;
        return $this;
    }

    public function getClamAvSocket(): string
    {
        return $this->_clamAvSocket;
    }



    public function uploadAll($destination, core\collection\IInputTree $inputCollection, $conflictAction = link\http\IUploadFile::RENAME)
    {
        foreach ($this as $file) {
            $file->upload($destination, $inputCollection->{$file->getFieldName()}, $conflictAction);
        }

        return $this;
    }

    public function tempUploadAll(core\collection\IInputTree $inputCollection)
    {
        foreach ($this as $file) {
            $file->tempUpload($inputCollection);
        }

        return $this;
    }


    // Countable
    public function count(): int
    {
        return count($this->_files);
    }

    // Iterator
    public function getIterator(): ArrayIterator
    {
        $output = [];

        foreach ($this->_files as $key => $set) {
            $output[$key] = new File($this, $key, $set);
        }

        return new ArrayIterator($output);
    }

    // Array access
    public function offsetSet(
        mixed $offset,
        mixed $value
    ): void {
    }

    public function offsetGet(mixed $offset): ?File
    {
        if (isset($this->_files[$offset])) {
            return new File($this, $offset, $this->_files[$offset]);
        }

        return null;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->_files[$offset]);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->_files[$offset]);
    }
}
