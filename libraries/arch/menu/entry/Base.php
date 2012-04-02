<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\menu\entry;

use df;
use df\core;
use df\arch;

abstract class Base implements arch\menu\IEntry {
    
    protected $_weight = 0;
    
    public static function fromArray(array $entry) {
        $type = 'Void';
        
        if(isset($entry['type'])) {
            $type = $entry['type'];
        }
        
        if(!$class = self::_getEntryClass($type)) {
            throw new arch\menu\EntryTypeNotFoundException(
                'Entry type '.$type.' could not be found'
            );
        }
        
        if(!isset($entry['id'])) {
            $entry['id'] = null;
        }
        
        if(!isset($entry['weight'])) {
            $entry['weight'] = 0;
        }
        
        return $class::_fromArray(array $entry);
    }
    
    protected static function _fromArray(array $entry) {
        $class = get_called_class();
        return (new $class())->setId($id)->setWeight($weight);
    }
    
    
    public static function factory($type) {
        return self::factoryArgs($type, array_slice(func_get_args(), 1));
    }
    
    public static function factoryArgs($type, array $args) {
        if(!$class = self::_getEntryClass($type)) {
            throw new arch\menu\EntryTypeNotFoundException(
                'Entry type '.$type.' could not be found'
            );
        }
        
        return (new \ReflectionClass($class))->newInstanceArgs($args);
    }
    
    
    protected static function _getEntryClass($type) {
        $class = 'df\\arch\\menu\\entry\\'.ucfirst($type);
        
        if(!class_exists($class)) {
            return null;
        }
        
        return $class;
    }
    
    
    public function __construct() {}
    
    public function getType() {
        $parts = explode('\\', get_class($this));
        return array_pop($parts);
    }
    
    public function setId($id) {
        if(empty($id)) {
            $id = null;
        }
        
        $this->_id = $id;
        return $this;
    }
    
    public function getId() {
        return $this->_id;
    }
    
    public function setWeight($weight) {
        $this->_weight = (float)$weight;
        return $this;
    }
    
    public function getWeight() {
        return $this->_weight;
    }
}
