<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\dumper;

use df;
use df\core;

class Inspector {
    
    protected static $_instanceCount = 0;
    
    protected $_arrayRefs = array();
    protected $_arrayRefHits = array();
    protected $_objectHashes = array();
    protected $_objectHashHits = array();
    
    public function __construct() {
        self::$_instanceCount++;
    }
    
    public function inspect(&$object, $deep=false) {
        if(is_null($object)) {
            require_once __DIR__.'/Immutable.php';
            return new Immutable(null);
            
        } else if(is_bool($object)) {
            require_once __DIR__.'/Immutable.php';
            return new Immutable($object);
            
        } else if(is_string($object)) {
            require_once __DIR__.'/String.php';
            return new String($object);
            
        } else if(is_numeric($object)) {
            require_once __DIR__.'/Number.php';
            return new Number($object);
            
        } else if(is_resource($object)) {
            require_once __DIR__.'/Resource.php';
            return new Resource($object);
            
        } else if(is_array($object)) {
            return $this->_dumpArray($object, $deep);
        } else if(is_object($object)) {
            return $this->_dumpObject($object, $deep);
        } else {
            throw new core\debug\RuntimeException('Unknown data type');
        }
    }
    
    
// Array
    protected function _dumpArray(array &$array, $deep=false) {
        if(null !== ($dumpId = $this->_getArrayDumpId($array))) {
            if(!isset($this->_arrayRefHits[$dumpId])) {
                $this->_arrayRefHits[$dumpId] = 0;
            }
            
            $this->_arrayRefHits[$dumpId]++;
            
            require_once __DIR__.'/Reference.php';
            return new Reference(null, $dumpId);
        }
        
        require_once __DIR__.'/Structure.php';
        $this->_registerArray($array);
        $properties = array();
        
        foreach(array_keys($array) as $key) {
            $properties[$key] = new Property($key, $array[$key], Property::VIS_PUBLIC, $deep);
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
            
            $array[$testKey] = &$testData;
            $isSame = isset($ref[$testKey]) && $ref[$testKey] === $testData;
            
            unset($array[$testKey]);
            
            if($isSame) {
                return $i + 1;
            }
        }
        
        return null;
    }
    
// Object
    protected function _dumpObject($object, $deep=false) {
        if(null !== ($dumpId = $this->_getObjectDumpId($object))) {
            if(!isset($this->_objectHashHits[$dumpId])) {
                $this->_objectHashHits[$dumpId] = 0;
            }
            
            $this->_objectHashHits[$dumpId]++;
            
            require_once __DIR__.'/Reference.php';
            return new Reference(get_class($object), $dumpId);
        }
        
        require_once __DIR__.'/Structure.php';
        $dumpId = $this->_registerObject($object);
        $properties = $this->_getObjectProperties($object, $deep);
        
        return new Structure($this, get_class($object), $dumpId, $properties);
    }
    
    protected function _getObjectProperties($object, $deep) {
        if(!$deep && $object instanceof core\IDumpable) {
            $properties = $object->getDumpProperties();
            
            if(!is_array($properties)) {
                $properties = [new Property(null, $properties, Property::VIS_PUBLIC, $deep)];
            }
            
            foreach($properties as $key => $property) {
                if(!$property instanceof Property) {
                    $properties[$key] = new Property($key, $property, Property::VIS_PUBLIC, $deep);
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
            
            $properties = array();
            
            // Inspect internal
            if($isInternal || $isParentInternal) {
                $properties = $this->_getInternalObjectProperties($object, $reflectionBase);
            }
            
            // Inspect userland
            if(!$isInternal) {
                $node = $reflection;
                $nodes = array();
                
                while($node) {
                    $nodes[] = $node;
                    $node = $node->getParentClass();
                }
                
                foreach(array_reverse($nodes) as $node) {
                    $nodeProperties = array();
                    
                    foreach($node->getProperties() as $refProperty) {
                        if($refProperty->isStatic()) {
                            continue;
                        }
                        
                        $refProperty->setAccessible(true);
                        $name = $refProperty->getName();
                        
                        if($refProperty->isPublic()) {
                            $visibility = Property::VIS_PUBLIC;
                        } else if($refProperty->isProtected()) {
                            $visibility = Property::VIS_PROTECTED;
                        } else {
                            $visibility = Property::VIS_PRIVATE;
                        }
                        
                        $value = $refProperty->getValue($object);
                        
                        if(isset($properties[$name]) && $properties[$name]->getValue() === $value) {
                            continue;
                        }
                        
                        $nodeProperties[$name] = new Property($name, $value, $visibility);
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
                $values = array();
                
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
                    new Property('flags', $object->getIteratorMode(), Property::VIS_PRIVATE),
                    new Property('dllist', $values, Property::VIS_PRIVATE)
                ];
                
            case 'SplPriorityQueue':
                $temp = clone $object;
                $temp->setExtractFlags(\SplPriorityQueue::EXTR_BOTH);
                $values = array();
                
                foreach($temp as $i => $val) {
                    $values[] = $val;
                }
                
                return [
                    new Property('flags', 1, Property::VIS_PRIVATE),
                    new Property('isCorrupted', false, Property::VIS_PRIVATE),
                    new Property('heap', $values, Property::VIS_PRIVATE)
                ];
                
            case 'SplHeap':
                $values = array();
                
                foreach(clone $object as $val) {
                    $values[] = $val;
                }
                
                return [
                    new Property('flags', 1, Property::VIS_PRIVATE),
                    new Property('isCorrupted', false, Property::VIS_PRIVATE),
                    new Property('heap', $values, Property::VIS_PRIVATE)
                ];
                
            case 'ReflectionClass':
            case 'ReflectionObject':
                return [
                    new Property('name', $object->name)
                ];
                
            case 'stdClass':
                $output = array();
                
                foreach((array)$object as $key => $value) {
                    $output[$key] = new Property($key, $value);
                }
                
                return $output;
                
            default:
                return array();
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
}
