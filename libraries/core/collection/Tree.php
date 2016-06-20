<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\collection;

use df;
use df\core;

class Tree implements ITree, ISeekable, ISortable, IAggregateIteratorCollection, \Serializable, core\IDumpable {

    use core\TValueMap;
    use core\TStringValueProvider;

    use TValueMapArrayAccess;
    use TArrayCollection;
    use TArrayCollection_Seekable;
    use TArrayCollection_ValueContainerSortable;
    use TArrayCollection_MappedMovable;

    protected $_value;

    public static function fromArrayDelimitedString($string, $setDelimiter='&', $valueDelimiter='=') {
        $output = new self();
        $parts = explode($setDelimiter, $string);

        foreach($parts as $part) {
            $valueParts = explode($valueDelimiter, trim($part), 2);

            $key = str_replace(']', '', urldecode(array_shift($valueParts)));
            $value = urldecode(array_shift($valueParts));

            $output->getNestedChild($key, '[')->setValue($value);
        }

        return $output;
    }

    public static function factory($input) {
        if($input instanceof ITree) {
            return $input;
        }

        return new self($input);
    }

    public function __construct($input=null, $value=null, $extractArray=false) {
        $this->setValue($value);

        if($input !== null) {
            $this->_import([$input], $extractArray);
        }
    }


    public function import(...$input) {
        return $this->_import($input);
    }

    protected function _import(array $input, $extractArray=true) {
        foreach($input as $data) {
            if($data instanceof ITree) {
                return $this->importTree($data);
            }

            if($extractArray && $data instanceof core\IArrayProvider) {
                $data = $data->toArray();
            }

            if(is_array($data)) {
                foreach($data as $key => $value) {
                    $this->__set($key, $value, $extractArray);
                }
            } else {
                $this->setValue($data);
            }
        }

        return $this;
    }

    public function importTree(ITree $input) {
        $this->_value = $input->_value;

        foreach($input->_collection as $key => $child) {
            unset($this->_collection[$key]);
            $this->{$key}->importTree($child);
        }

        return $this;
    }

    public function merge(ITree $input) {
        $this->_value = $input->_value;

        foreach($input->_collection as $key => $child) {
            $this->{$key}->importTree($child);
        }

        return $this;
    }


// Serialize
    public function serialize() {
        return serialize($this->_getSerializeValues());
    }

    protected function _getSerializeValues() {
        $output = [];

        if($this->_value !== null) {
            $output['vl'] = $this->_value;
        }

        if(!empty($this->_collection)) {
            $children = [];

            foreach($this->_collection as $key => $child) {
                $children[$key] = $child->_getSerializeValues();
            }

            $output['cd'] = $children;
        }

        if(empty($output)) {
            $output = null;
        }

        return $output;
    }

    public function unserialize($data) {
        if(is_array($values = unserialize($data))) {
            $this->_setUnserializedValues($values);
        }

        return $this;
    }

    protected function _setUnserializedValues(array $values) {
        if(isset($values['vl'])) {
            $this->_value = $values['vl'];
        }

        if(isset($values['cd'])) {
            $class = get_class($this);

            foreach($values['cd'] as $key => $childData) {
                $child = new $class();

                if(!empty($childData)) {
                    $child->_setUnserializedValues($childData);
                }

                $this->_collection[$key] = $child;
            }
        }
    }


// Collection
    public function clear() {
        $this->_value = null;
        $this->_collection = [];
        return $this;
    }

    public function getReductiveIterator() {
        return new ReductiveMapIterator($this);
    }


// Clone
    public function __clone() {
        foreach($this->_collection as $key => $child) {
            $this->_collection[$key] = clone $child;
        }

        return $this;
    }


// Access
    public function getNestedChild($parts, $separator='.') {
        if(!is_array($parts)) {
            $parts = explode($separator, $parts);
        }

        $node = $this;

        while(null !== ($part = array_shift($parts))) {
            if(!strlen($part)) {
                if(!empty($node->_collection)) {
                    $part = max(array_keys($node->_collection)) + 1;
                } else {
                    $part = 0;
                }
            }

            $node = $node->{$part};
        }

        return $node;
    }

    public function getKeys() {
        return array_keys($this->_collection);
    }

    public function getChildren() {
        return $this->_collection;
    }

    public function contains($value, $includeChildren=false) {
        foreach($this->_collection as $child) {
            if($child->_value == $value
            || ($includeChildren && $child->contains($value, true))) {
                return true;
            }
        }

        return false;
    }


    public function __set($key, $value) {
        return $this->_set($key, $value, false);
    }

    public function _set($key, $value, $extractArray=false) {
        if(isset($this->_collection[$key])) {
            $this->_collection[$key]->_collection = [];
            $this->_collection[$key]->import($value);
        } else {
            $class = get_class($this);
            $this->_collection[$key] = new $class($value, null, $extractArray);
        }

        return $this;
    }

    public function __get($key) {
        if(!array_key_exists($key, $this->_collection)) {
            $class = get_class($this);
            $this->_collection[$key] = new $class();
        }

        return $this->_collection[$key];
    }

    public function __isset($key) {
        return array_key_exists($key, $this->_collection);
    }

    public function __unset($key) {
        unset($this->_collection[$key]);
        return $this;
    }



    public function set($key, $value) {
        $this->__get($key)->setValue($value);
        return $this;
    }

    public function get($key, $default=null) {
        if(!array_key_exists($key, $this->_collection)) {
            return $default;
        }

        return $this->_collection[$key]->getValue($default);
    }

    public function has(...$keys) {
        foreach($keys as $key) {
            if(array_key_exists($key, $this->_collection)
            && $this->_collection[$key]->hasValue()) {
                return true;
            }
        }

        return false;
    }

    public function hasKey(...$keys) {
        foreach($keys as $key) {
            if(array_key_exists($key, $this->_collection)) {
                return true;
            }
        }

        return false;
    }

    public function remove(...$keys) {
        foreach($keys as $key) {
            unset($this->_collection[$key]);
        }

        return $this;
    }

    public function offsetSet($key, $value) {
        if($key === null) {
            return $this->push($value);
        }

        return $this->__set($key, $value);
    }

    public function clearKeys() {
        $this->_collection = array_values($this->_collection);
        return $this;
    }


// Shiftable
    public function extract() {
        return $this->shift();
    }

    public function insert(...$values) {
        return $this->push(...$values);
    }

    public function pop() {
        return array_pop($this->_collection);
    }

    public function push(...$values) {
        $class = get_class($this);

        array_walk($values, function(&$value) use($class) {
            $value = new $class($value);
        });

        array_push($this->_collection, ...$values);
        return $this;
    }

    public function shift() {
        return array_shift($this->_collection);
    }

    public function unshift(...$values) {
        $class = get_class($this);

        array_walk($values, function(&$value) use($class) {
            $value = new $class($value);
        });

        array_unshift($this->_collection, ...$values);
        return $this;
    }



// Value container
    public function setValue($value) {
        $this->_value = $value;
        return $this;
    }

    public function getValue($default=null) {
        if($this->_value === null) {
            return $default;
        }

        return $this->_value;
    }

    public function hasValue(): bool {
        //return $this->_value !== null;
        return !empty($this->_value) || $this->_value === '0';
    }

    public function hasAnyValue(array $checkKeys=null) {
        if($this->hasValue()) {
            return true;
        }

        foreach($this->_collection as $key => $child) {
            if($checkKeys !== null && !in_array($key, $checkKeys)) {
                continue;
            }

            if($child->hasAnyValue($checkKeys)) {
                return true;
            }
        }

        return false;
    }

    public function getStringValue($default=''): string {
        return $this->_getStringValue($this->_value, $default);
    }


// String provider
    public function __toString(): string {
        try {
            return (string)$this->toString();
        } catch(\Exception $e) {
            return (string)$this->_value;
        } catch(\Error $e) {
            return (string)$this->_value;
        }
    }

    public function toString(): string {
        return $this->getStringValue();
    }

    public function toArrayDelimitedString($setDelimiter='&', $valueDelimiter='=') {
        $output = [];

        foreach($this->toUrlEncodedArrayDelimitedSet() as $key => $value) {
            if(!empty($value) || $value === '0' || $value === 0) {
                $output[] = $key.$valueDelimiter.rawurlencode($value);
            } else {
                $output[] = $key;
            }
        }

        return implode($setDelimiter, $output);
    }


// Array provider
    public function toArray() {
        $output = [];

        foreach($this->_collection as $key => $child) {
            if($child->count()) {
                $output[$key] = $child->toArray();
            } else {
                $output[$key] = $child->getValue();
            }
        }

        return $output;
    }

    public function toArrayDelimitedSet($prefix=null) {
        $output = [];

        if($prefix
        && ($this->_value !== null || empty($this->_collection))) {
            $output[$prefix] = $this->getValue();
        }

        foreach($this as $key => $child) {
            if($prefix) {
                $key = $prefix.'['.$key.']';
            }

            $output = array_merge($output, $child->toArrayDelimitedSet($key));
        }

        return $output;
    }

    public function toUrlEncodedArrayDelimitedSet($prefix=null) {
        $output = [];

        if($prefix
        && ($this->_value !== null || empty($this->_collection))) {
            $output[$prefix] = $this->getValue();
        }

        foreach($this as $key => $child) {
            if($prefix) {
                $key = $prefix.'['.rawurlencode($key).']';
            }

            $output = array_merge($output, $child->toUrlEncodedArrayDelimitedSet($key));
        }

        return $output;
    }


// Dump
    public function getDumpProperties() {
        $children = [];

        foreach($this->_collection as $key => $child) {
            if($child instanceof self && empty($child->_collection)) {
                $children[$key] = $child->_value;
            } else {
                $children[$key] = $child;
            }
        }

        if(empty($children)) {
            return $this->_value;
        }

        if(!empty($this->_value)) {
            array_unshift($children, new core\debug\dumper\Property(null, $this->_value, 'protected'));
        }

        return $children;
    }
}
