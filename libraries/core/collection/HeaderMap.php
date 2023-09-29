<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\core\collection;

use DecodeLabs\Exceptional;

use DecodeLabs\Glitch\Dumpable;
use df\core;

class HeaderMap implements IHeaderMap, Dumpable
{
    use core\TStringProvider;
    use core\TValueMap;
    use TArrayCollection;
    use TValueMapArrayAccess;
    use TExtricable;

    public static function factory($input)
    {
        if ($input instanceof IHeaderMap) {
            return $input;
        }

        return new self($input);
    }

    public function __construct(...$input)
    {
        if (!empty($input)) {
            $this->import(...$input);
        }
    }

    // Collection
    public function import(...$input)
    {
        foreach ($input as $data) {
            if ($data instanceof core\IArrayProvider) {
                $data = $data->toArray();
            }

            if (is_string($data)) {
                $lines = explode("\n", str_replace("\r", '', $data));
                $data = [];
                $last = null;

                foreach ($lines as $line) {
                    if (isset($line[0]) && $line[0] == ' ') {
                        $last .= "\r\n" . $line;
                        continue;
                    }

                    $parts = explode(':', $line, 2);
                    $key = trim((string)array_shift($parts));

                    if (empty($key)) {
                        continue;
                    }

                    $value = trim((string)array_shift($parts));
                    $data[$key] = $value;
                    $last = &$data[$key];
                }
                unset($last);
            }

            if (is_array($data)) {
                foreach ($data as $key => $value) {
                    $this->set($key, $value);
                }
            }
        }

        return $this;
    }


    // Access
    public function set($key, $value = null)
    {
        if (empty($key)) {
            throw Exceptional::InvalidArgument(
                'Invalid header input'
            );
        }

        $key = $this->normalizeKey($key);

        if (is_array($value)) {
            $this->_collection[$key] = [];

            foreach ($value as $k => $val) {
                $this->add($key, $val);
            }

            return $this;
        }

        if (isset($value)) {
            $this->_collection[$key] = $value;
        } else {
            unset($this->_collection[$key]);
        }

        return $this;
    }

    public function add($key, $value)
    {
        if (empty($key) || (isset($value) && !is_scalar($value))) {
            throw Exceptional::InvalidArgument(
                'Invalid header input'
            );
        }

        $key = $this->normalizeKey($key);

        if ($value === null) {
            return $this;
        }

        if (!isset($this->_collection[$key])) {
            $this->_collection[$key] = $value;
            return $this;
        }

        if (!is_array($this->_collection[$key])) {
            $this->_collection[$key] = [$this->_collection[$key]];
        }

        $this->_collection[$key][] = $value;

        return $this;
    }

    public function append($key, $value)
    {
        if (empty($key) || (isset($value) && !is_scalar($value))) {
            throw Exceptional::InvalidArgument(
                'Invalid header input'
            );
        }

        $key = $this->normalizeKey($key);

        if ($value === null) {
            return $this;
        }

        if (!isset($this->_collection[$key])) {
            $this->_collection[$key] = '';
        }

        if (is_array($this->_collection[$key])) {
            end($this->_collection[$key]);
            $lastKey = key($this->_collection[$key]);
            $this->_collection[$key][$lastKey] .= $value;
        } else {
            $this->_collection[$key] .= $value;
        }

        return $this;
    }

    public function get($key, $default = null)
    {
        $key = $this->normalizeKey($key);

        if (isset($this->_collection[$key])) {
            return $this->_collection[$key];
        }

        return $default;
    }

    public function setBase($key, $value)
    {
        $parts = explode(';', $this->get($key), 2);
        $parts[0] = $value;

        return $this->set($key, implode(';', $parts));
    }

    public function getBase($key, $default = null)
    {
        if (null === ($output = $this->get($key, $default))) {
            return null;
        }

        $parts = explode(';', (string)$output, 2);
        return array_shift($parts);
    }

    public function setDelimited($key, $base, array $values)
    {
        $value = $base . '; ' . core\collection\Tree::factory($values)->toArrayDelimitedString(';');
        return $this->set($key, $value);
    }

    public function getDelimited($key): ITree
    {
        $value = $this->get($key);
        return core\collection\Tree::fromArrayDelimitedString('@value=' . $value, ';');
    }

    public function setDelimitedValues($key, array $values)
    {
        $value = $this->get($key);

        if ($value === null) {
            return $this;
        }

        $parts = core\collection\Tree::fromArrayDelimitedString('@value=' . $value, ';');
        $value = $parts['@value'];
        $parts->remove('@value');
        $parts->import($values);

        $value .= '; ' . $parts->toArrayDelimitedString(';');
        return $this->set($key, $value);
    }

    public function getDelimitedValues($key): array
    {
        $value = $this->get($key);
        $parts = core\collection\Tree::fromArrayDelimitedString('@value=' . $value, ';');
        $parts->remove('@value');
        return $parts->toArray();
    }

    public function setDelimitedValue($key, $name, $keyValue)
    {
        $value = $this->get($key);

        if ($value === null) {
            return $this;
        }

        $parts = core\collection\Tree::fromArrayDelimitedString('@value=' . $value, ';');
        $value = $parts['@value'];
        $parts->remove('@value');
        $parts->set($name, $keyValue);

        $value .= '; ' . $parts->toArrayDelimitedString(';');
        return $this->set($key, $value);
    }

    public function getDelimitedValue($key, $name, $default = null)
    {
        $value = $this->get($key);

        if ($value === null) {
            return $default;
        }

        $parts = core\collection\Tree::fromArrayDelimitedString('@value=' . $value, ';');
        return trim($parts->get($name, $default), '"');
    }

    public function hasDelimitedValue($key, $name)
    {
        $value = $this->get($key);

        if ($value === null) {
            return false;
        }

        return (bool)preg_match('/\;\W*' . preg_quote($name) . '=/i', $value);
    }

    public function has(...$keys)
    {
        foreach ($keys as $key) {
            $key = $this->normalizeKey($key);

            if (isset($this->_collection[$key])) {
                return true;
            }
        }

        return false;
    }

    public function hasValue($key, $value): bool
    {
        $key = $this->normalizeKey($key);

        if (!isset($this->_collection[$key])) {
            return false;
        }

        $comp = $this->_collection[$key];

        if (!is_array($comp)) {
            $comp = [$comp];
        }

        $value = strtolower((string)$value);

        foreach ($comp as $compVal) {
            $compVal = strtolower((string)$compVal);

            if ($compVal == $value) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$keys)
    {
        foreach ($keys as $key) {
            unset($this->_collection[$this->normalizeKey($key)]);
        }

        return $this;
    }

    public static function normalizeKey($key)
    {
        return str_replace(
            ' ',
            '-',
            ucwords(strtolower(
                str_replace(['-', '_'], ' ', $key)
            ))
        );
    }


    // Strings
    public function toString(array $skipKeys = null): string
    {
        return implode("\r\n", $this->getLines($skipKeys));
    }

    public function getLines(array $skipKeys = null)
    {
        $output = [];

        if ($skipKeys) {
            foreach ($skipKeys as $i => $key) {
                $skipKeys[$i] = self::normalizeKey($key);
            }
        }

        foreach ($this->_collection as $key => $value) {
            if ($skipKeys && in_array($key, $skipKeys)) {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $v) {
                    $output[] = $key . ': ' . $this->_formatValue($key, $v);
                }
            } else {
                $output[] = $key . ': ' . $this->_formatValue($key, $value);
            }
        }

        return $output;
    }

    protected function _formatValue($key, $value)
    {
        if ($value instanceof core\time\IDate) {
            return $value->toTimeZone('GMT')->format('D, d M Y H:i:s \G\M\T');
        }

        return (string)$value;
    }



    // Iterator
    public function current(): mixed
    {
        $output = current($this->_collection);

        if (is_array($output)) {
            $output = current($output);
        }

        return $output;
    }

    public function next(): void
    {
        $key = key($this->_collection);

        if (is_array($this->_collection[$key])) {
            $output = next($this->_collection[$key]);

            if (key($this->_collection[$key]) !== null) {
                return;
            }
        }

        next($this->_collection);
    }

    public function key(): mixed
    {
        return key($this->_collection);
    }

    public function rewind(): void
    {
        foreach ($this->_collection as $key => &$value) {
            if (is_array($value)) {
                reset($value);
            }
        }

        reset($this->_collection);
    }

    public function valid(): bool
    {
        return key($this->_collection) !== null;
    }


    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'values' => $this->_collection;
    }
}
