<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

df\Launchpad::loadBaseClass('core/debug/dumper/_manifest');
df\Launchpad::loadBaseClass('core/debug/dumper/Property');

class Inspector implements IInspector {

    protected static $_instanceCount = 0;

    protected $_arrayRefs = [];
    protected $_arrayRefHits = [];
    protected $_objectHashes = [];
    protected $_objectHashHits = [];

    public function __construct() {
        self::$_instanceCount++;
    }

    public static function getInstanceCount() {
        return self::$_instanceCount;
    }

    public function inspect($object, $deep=false) {
        if(is_null($object)) {
            df\Launchpad::loadBaseClass('core/debug/dumper/Immutable');
            return new Immutable($this, null);

        } else if(is_bool($object)) {
            df\Launchpad::loadBaseClass('core/debug/dumper/Immutable');
            return new Immutable($this, $object);

        } else if(is_string($object)) {
            df\Launchpad::loadBaseClass('core/debug/dumper/Text');
            return new Text($this, $object);

        } else if(is_numeric($object)) {
            df\Launchpad::loadBaseClass('core/debug/dumper/Number');
            return new Number($this, $object);

        } else if(is_resource($object)) {
            df\Launchpad::loadBaseClass('core/debug/dumper/NativeResource');
            return new NativeResource($this, $object);

        } else if(is_array($object)) {
            return $this->_dumpArray($object, $deep);
        } else if(is_object($object)) {
            return $this->_dumpObject($object, $deep);
        } else {
            df\Launchpad::loadBaseClass('core/debug/dumper/Text');
            return new Text($this, (string)$object);
            //throw new core\debug\RuntimeException('Unknown data type');
        }
    }


// Array
    protected function _dumpArray(array &$array, $deep=false) {
        if(null !== ($dumpId = $this->_getArrayDumpId($array))) {
            if(!isset($this->_arrayRefHits[$dumpId])) {
                $this->_arrayRefHits[$dumpId] = 0;
            }

            $this->_arrayRefHits[$dumpId]++;

            df\Launchpad::loadBaseClass('core/debug/dumper/Reference');
            return new Reference($this, null, $dumpId);
        }

        df\Launchpad::loadBaseClass('core/debug/dumper/Structure');
        $this->_registerArray($array);
        $properties = [];

        foreach(array_keys($array) as $key) {
            $properties[$key] = new Property($key, $array[$key], IProperty::VIS_PUBLIC, $deep);
        }

        return new Structure($this, null, $dumpId, $properties);
    }

    protected function _registerArray(array &$array) {
        $this->_arrayRefs[] = &$array;
        return count($this->_arrayRefs);
    }

    protected function _getArrayDumpId(array &$array) {
        do {
            $testKey = uniqid('__refId', true);
        } while(isset($array[$testKey]));

        $testData = uniqid('refData', true);

        foreach($this->_arrayRefs as $i => &$ref) {
            if(isset($ref[$testKey])) {
                continue;
            }

            $array[$testKey] = $testData;
            $isSame = isset($ref[$testKey]) && $ref[$testKey] === $testData;

            unset($array[$testKey]);

            if($isSame) {
                return $i + 1;
            }
        }

        return null;
    }

    public function countArrayRefHits($dumpId) {
        if(isset($this->_arrayRefHits[$dumpId])) {
            return $this->_arrayRefHits[$dumpId];
        }

        return 0;
    }

// Object
    public function inspectObjectProperties($object, $deep=false) {
        return new Structure($this, $this->_normalizeObjectClass(get_class($object)), 0, $this->_getObjectProperties($object, $deep));
    }

    protected function _normalizeObjectClass(string $class): string {
        $name = [];
        $parts = explode(':', $class);

        while(!empty($parts)) {
            $part = trim(array_shift($parts));

            if(preg_match('/^class@anonymous(.+)(\(([0-9]+)\))/', $part, $matches)) {
                $name[] = core\fs\Dir::stripPathLocation($matches[1]).' : '.($matches[3] ?? null);
            } else if(preg_match('/^eval\(\)\'d/', $part)) {
                $name = ['eval[ '.implode(' : ', $name).' ]'];
            } else {
                $name[] = $part;
            }
        }

        return implode(' : ', $name);
    }


    protected function _dumpObject($object, $deep=false) {
        if(null !== ($dumpId = $this->_getObjectDumpId($object))) {
            if(!isset($this->_objectHashHits[$dumpId])) {
                $this->_objectHashHits[$dumpId] = 0;
            }

            $this->_objectHashHits[$dumpId]++;

            df\Launchpad::loadBaseClass('core/debug/dumper/Reference');
            return new Reference($this, get_class($object), $dumpId);
        }

        df\Launchpad::loadBaseClass('core/debug/dumper/Structure');
        $dumpId = $this->_registerObject($object);
        $properties = $this->_getObjectProperties($object, $deep);

        return new Structure($this, get_class($object), $dumpId, $properties);
    }

    protected function _getObjectProperties($object, $deep) {
        if(!$deep && $object instanceof core\IDumpable) {
            $properties = $object->getDumpProperties();

            if(!is_array($properties)) {
                $properties = [new Property(null, $properties, IProperty::VIS_PUBLIC, $deep)];
            }

            foreach($properties as $key => $property) {
                if(!$property instanceof Property) {
                    $properties[$key] = new Property($key, $property, IProperty::VIS_PUBLIC, $deep);
                }
            }
        } else {
            $reflection = new \ReflectionObject($object);
            $isInternal = $isParentInternal = $reflection->isInternal();
            $reflectionBase = $reflection;

            // Check base is internal
            if(!$isParentInternal) {
                while(true) {
                    $isParentInternal = $reflectionBase->isInternal();

                    if(!$ref = $reflectionBase->getParentClass()) {
                        break;
                    }

                    $reflectionBase = $ref;
                }
            }

            $properties = [];

            // Inspect internal
            if($isInternal || $isParentInternal) {
                $properties = $this->_getInternalObjectProperties($object, $reflectionBase);
            }

            // Inspect userland
            if(!$isInternal) {
                $node = $reflection;
                $nodes = [];

                while($node) {
                    $nodes[] = $node;
                    $node = $node->getParentClass();
                }

                foreach(array_reverse($nodes) as $node) {
                    $nodeProperties = [];

                    foreach($node->getProperties() as $refProperty) {
                        if($refProperty->isStatic()) {
                            continue;
                        }

                        $refProperty->setAccessible(true);
                        $name = $refProperty->getName();

                        if($refProperty->isPublic()) {
                            $visibility = IProperty::VIS_PUBLIC;
                        } else if($refProperty->isProtected()) {
                            $visibility = IProperty::VIS_PROTECTED;
                        } else {
                            $visibility = IProperty::VIS_PRIVATE;
                        }

                        $value = $refProperty->getValue($object);

                        if(isset($properties[$name]) && $properties[$name]->getValue() === $value) {
                            continue;
                        }

                        $nodeProperties[$name] = new Property($name, $value, $visibility, $deep);
                    }

                    $properties = array_merge($nodeProperties, $properties);
                }
            }

        }

        return $properties;
    }

    protected function _getInternalObjectProperties($object, $reflection) {
        switch($reflection->getName()) {
            case 'SplDoublyLinkedList':
                $isStack = \SplDoublyLinkedList::IT_MODE_LIFO & $object->getIteratorMode();
                $values = [];

                foreach(clone $object as $i => $value) {
                    if($isStack) {
                        $values[$i] = $value;
                    } else {
                        $values[] = $value;
                    }
                }

                if($isStack) {
                    ksort($values);
                }

                return [
                    new Property('flags', $object->getIteratorMode(), IProperty::VIS_PRIVATE),
                    new Property('dllist', $values, IProperty::VIS_PRIVATE)
                ];

            case 'SplPriorityQueue':
                $temp = clone $object;
                $temp->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);

                $output = [
                    new Property('flags', 1, IProperty::VIS_PRIVATE),
                    new Property('isCorrupted', false, IProperty::VIS_PRIVATE)
                ];

                foreach($temp as $i => $val) {
                    $output[] = new Property($val['priority'], $val['data']);
                }

                return $output;

            case 'SplHeap':
                $output = [
                    new Property('flags', 1, IProperty::VIS_PRIVATE),
                    new Property('isCorrupted', false, IProperty::VIS_PRIVATE)
                ];

                foreach(clone $object as $i => $val) {
                    $output[] = new Property($i, $val);
                }

                return $output;

            case 'ReflectionClass':
            case 'ReflectionObject':
                return [
                    new Property('name', $object->name)
                ];

            case 'stdClass':
                $output = [];

                foreach((array)$object as $key => $value) {
                    $output[$key] = new Property($key, $value);
                }

                return $output;

            default:
                return [];
        }
    }

    protected function _registerObject($object) {
        $this->_objectHashes[spl_object_hash($object)] = $dumpId = count($this->_objectHashes) + 1;
        return $dumpId;
    }

    protected function _getObjectDumpId($object) {
        $hash = spl_object_hash($object);

        if(isset($this->_objectHashes[$hash])) {
            return $this->_objectHashes[$hash];
        }

        return null;
    }

    public function countObjectHashHits($dumpId) {
        if(isset($this->_objectHashHits[$dumpId])) {
            return $this->_objectHashHits[$dumpId];
        }

        return 0;
    }
}
