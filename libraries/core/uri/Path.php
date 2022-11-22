<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\uri;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\core;

class Path implements IPath, \IteratorAggregate, \Serializable, Dumpable
{
    use core\TStringProvider;
    use core\collection\TArrayCollection;
    use core\collection\TArrayCollection_ProcessedIndexedValueMap;
    use core\collection\TArrayCollection_Seekable;
    use core\collection\TArrayCollection_Sliceable;
    use core\collection\TArrayCollection_ProcessedShiftable;
    use core\collection\TArrayCollection_IndexedMovable;

    protected $_separator = '/';
    protected $_autoCanonicalize = true;
    protected $_isAbsolute = false;
    protected $_addTrailingSlash = false;

    public static function normalizeLocal($path): string
    {
        $path = self::factory($path);
        $queue = $path->_collection;
        $path->_collection = [];

        foreach ($queue as $key => $part) {
            if ($part == '..') {
                if (empty($path->_collection)) {
                    throw Exceptional::Runtime(
                        'Invalid local path'
                    );
                }

                array_pop($path->_collection);
            }

            if ($part != '.' && strlen($part)) {
                $path->_collection[] = $part;
            }
        }

        return (string)$path;
    }

    public static function extractFileName($path)
    {
        return self::factory($path)->getFileName();
    }

    public static function extractRootFileName($path)
    {
        $parts = explode('.', basename($path));
        return array_shift($parts);
    }

    public static function extractExtension($path)
    {
        return self::factory($path)->getExtension();
    }

    public static function factory(...$args)
    {
        if (func_num_args()) {
            $path = func_get_arg(0);

            if ($path instanceof IPath) {
                return $path;
            }
        }

        $ref = new \ReflectionClass(get_called_class());
        return $ref->newInstanceArgs($args);
    }

    public function __construct($input = null, $autoCanonicalize = false, $separator = null)
    {
        $this->canAutoCanonicalize($autoCanonicalize);

        if ($separator !== null) {
            $this->setSeparator($separator);
        }

        if ($input !== null) {
            $this->import($input);
        }
    }

    // Serialize
    public function serialize()
    {
        return $this->toString();
    }

    public function unserialize(string $data): void
    {
        $this->import($data);
    }

    public function __serialize(): array
    {
        return [
            $this->toString()
        ];
    }

    public function __unserialize(array $data): void
    {
        $this->import($data[0]);
    }


    // Parameters
    public function setSeparator($separator)
    {
        if ($separator !== null) {
            $this->_separator = (string)$separator;
        }

        return $this;
    }

    public function getSeparator()
    {
        return $this->_separator;
    }

    public function isAbsolute(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_isAbsolute = $flag;
            return $this;
        }

        return $this->hasWinDrive() || $this->_isAbsolute;
    }

    public function shouldAddTrailingSlash(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_addTrailingSlash = $flag;
            return $this;
        }

        return $this->_addTrailingSlash;
    }

    public function canAutoCanonicalize(bool $flag = null)
    {
        if ($flag !== null) {
            $this->_autoCanonicalize = $flag;
            return $this;
        }

        return $this->_autoCanonicalize;
    }

    public function canonicalize()
    {
        if (in_array('.', $this->_collection)
        || in_array('..', $this->_collection)
        || in_array('', $this->_collection)) {
            $queue = $this->_collection;
            $this->_collection = [];

            foreach ($queue as $key => $part) {
                if ($part == '..' && !$this->isEmpty() && $this->getLast() != '..') {
                    array_pop($this->_collection);
                    continue;
                }

                if ($part != '.' && strlen($part)) {
                    $this->_collection[] = $part;
                }
            }
        }

        return $this;
    }

    public function extractRelative($path)
    {
        if (!is_array($path)) {
            if ($path instanceof core\IArrayProvider) {
                $path = $path->toArray();
            } else {
                $path = explode('/', $path);
            }
        } else {
            $path = array_values($path);
        }

        $parts = $this->_collection;

        if (!$this->_addTrailingSlash) {
            array_pop($parts);
        }

        $parts = array_merge($parts, $path);
        return new self($parts, true);
    }



    // Collection
    public function getRawCollection()
    {
        return $this->_collection;
    }

    public function toArray(): array
    {
        $output = $this->_collection;

        if ($this->_addTrailingSlash) {
            $output[] = '';
        }

        return $output;
    }

    public function import(...$input)
    {
        if (count($input) > 1) {
            $input = implode($this->_separator, $input);
        } else {
            $input = array_shift($input);
        }

        if ($input === null) {
            return $this;
        }

        if ($input instanceof self) {
            $this->_collection = $input->_collection;
            $this->_isAbsolute = $input->_isAbsolute;
            $this->_autoCanonicalize = $input->_autoCanonicalize;
            $this->_addTrailingSlash = $input->_addTrailingSlash;
            $this->_separator = $input->_separator;

            return $this;
        }


        $this->clear();


        if ($input instanceof core\collection\ICollection) {
            $input = $input->toArray();
        } elseif (!is_array($input)) {
            if (!empty($input)) {
                $input = explode(
                    $this->_separator,
                    str_replace(['\\', '/'], $this->_separator, (string)$input)
                );
            } else {
                $input = [];
            }
        }

        if (!($count = count($input))) {
            $this->_addTrailingSlash = false;
            return $this;
        }

        // Strip trailing slash
        if ($count > 1 && !strlen(trim($input[$count - 1]))) {
            array_pop($input);
            $this->_addTrailingSlash = true;
        }

        // Strip leading slash
        if (!isset($input[0]) || !strlen($input[0])) {
            array_shift($input);
            $this->_isAbsolute = true;
        }

        // Fill values
        foreach ($input as $value) {
            $this->_collection[] = trim($value);
        }


        // Canonicalize
        if ($this->_autoCanonicalize) {
            $this->canonicalize();
        }

        return $this;
    }

    public function clear()
    {
        $this->_isAbsolute = false;
        $this->_addTrailingSlash = false;
        $this->_collection = [];

        return $this;
    }

    public function insert(...$values)
    {
        return $this->push(...$values);
    }

    protected function _onInsert()
    {
        if (!strlen($this->getLast())) {
            array_pop($this->_collection);
            $this->_addTrailingSlash = true;
        }

        if ($this->_autoCanonicalize) {
            $this->canonicalize();
        }
    }

    protected function _expandInput($input): array
    {
        /*
        if ($input instanceof core\collection\ICollection) {
            $input = $input->toArray();
        }
         */

        if (is_array($input)) {
            return $input;
        }

        $input = (string)$input;

        if (!strlen($input)) {
            return [];
        }

        return explode($this->_separator, ltrim($input, $this->_separator));
    }



    // Accessors
    public function getDirname()
    {
        return dirname($this->toString() . 'a') . '/';
    }

    public function setBaseName($baseName)
    {
        $t = $this->_autoCanonicalize;
        $this->_autoCanonicalize = false;

        $this->set(-1, $baseName);
        $this->_autoCanonicalize = $t;

        return $this;
    }

    public function getBaseName()
    {
        return $this->getLast();
    }

    public function setFileName($fileName)
    {
        if ($this->_addTrailingSlash) {
            $this->_collection[] = $fileName;
            $this->_addTrailingSlash = false;
            return $this;
        }

        if (
            strlen((string)($extension = $this->getExtension())) ||
            substr($this->getLast(), -1) == '.'
        ) {
            $fileName .= '.' . $extension;
        }

        return $this->setBaseName($fileName);
    }

    public function getFileName()
    {
        if ($this->_addTrailingSlash) {
            return null;
        }

        $baseName = $this->getBaseName();

        if (false === ($pos = strrpos($baseName, '.'))) {
            return $baseName;
        }

        return substr($baseName, 0, $pos);
    }

    public function hasExtension(...$extensions)
    {
        if ($this->_addTrailingSlash) {
            return false;
        }

        if (($baseName = $this->getBaseName()) == '..') {
            return false;
        }

        if (empty($extensions)) {
            return false !== strrpos($baseName, '.');
        }

        if (is_string($extension = $this->getExtension())) {
            $extension = strtolower($extension);
        }

        array_walk($extensions, 'strtolower');
        return in_array($extension, $extensions, true);
    }

    public function setExtension($extension)
    {
        $fileName = $this->getFileName();

        if ($extension !== null) {
            $fileName .= '.' . $extension;
        }

        if (strlen($fileName)) {
            if ($this->_addTrailingSlash) {
                $this->_collection[] = $fileName;
                $this->_addTrailingSlash = false;
                return $this;
            } else {
                return $this->setBaseName($fileName);
            }
        }

        return $this;
    }

    public function getExtension()
    {
        if ($this->_addTrailingSlash) {
            return null;
        }

        $baseName = $this->getBaseName();

        if (false === ($pos = strrpos($baseName, '.'))) {
            return null;
        }

        $length = strlen($baseName);

        if ($pos === $length) {
            return null;
        }

        return substr($baseName, $pos + 1);
    }



    // Win
    public function hasWinDrive()
    {
        return isset($this->_collection[0]) && preg_match('/^[a-zA-Z]\:$/', $this->_collection[0]);
    }

    public function getWinDriveLetter()
    {
        if (!isset($this->_collection[0])) {
            return null;
        }

        if (!preg_match('/^([a-zA-Z])\:$/', $this->_collection[0], $matches)) {
            return null;
        }

        return strtolower($matches[1]);
    }


    // Strings
    public function toString(): string
    {
        return $this->_pathToString(false);
    }

    public function toUrlEncodedString()
    {
        return $this->_pathToString(true);
    }

    protected function _pathToString($encode = false)
    {
        $output = '';
        $separator = $this->_separator;

        if ($isWin = (!$encode && $this->hasWinDrive())) {
            $separator = '\\';
        }

        if ($this->_isAbsolute && !$isWin) {
            $output .= $separator;
        }

        foreach ($this->_collection as $key => $value) {
            if ($key > 0) {
                $output .= $separator;
            }

            if ($encode) {
                $value = rawurlencode($value);
            }

            $output .= $value;
        }

        if (!strlen($output) && !$this->_isAbsolute) {
            $output = '.';
        }

        if (($this->_addTrailingSlash || empty($output))
        && $output != $separator
        && !$this->hasExtension()) {
            $output .= $separator;
        }

        return $output;
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'text' => $this->toString();
        yield 'classMembers' => [];
        yield 'section:properties' => false;
    }
}
