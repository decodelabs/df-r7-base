<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\policy;

use df;
use df\core;

class EntityLocator implements IEntityLocator, core\IDumpable {
    
    protected $_scheme;
    protected $_nodes = array();
    
    public static function factory($locator) {
        if($locator instanceof IEntityLocator) {
            return $locator;
        }
        
        return new self($locator);
    }
    
    public function __construct($locator) {
        if($locator instanceof core\uri\IGenericUrl) {
            $this->_scheme = $locator->getScheme();
            $path = $locator->getPath()->toString();
        } else {
            $parts = explode('://', $locator, 2);
            $this->_scheme = array_shift($parts);
            $path = array_shift($parts);
        }
        
        $this->_splitEntityPath($path);
    }
    
    private function _splitEntityPath($path) {
        $path = trim($path, '/').'/';
        $length = strlen($path);
        $mode = 0;
        $part = '';
        
        $node = new EntityLocatorNode();
        
        for($i = 0; $i < $length; $i++) {
            $char = $path{$i};
            
            switch($mode) {
                // Location
                case 0:
                    if(!isset($part{0}) && ctype_upper($char)) {
                        $part .= $char;
                        $mode = 1; // Type
                    } else if($char == '/') {
                        $node->appendLocation($part);
                        $part = '';
                    } else if(ctype_alpha($char)) {
                        $part .= $char;
                    } else {
                        throw new InvalidArgumentException(
                            'Unexpected char: '.$char.' in locator: '.$path.' at char: '.$i
                        );
                    }
                    
                    break;
                    
                // Entity type name
                case 1:
                    if($char == ':') {
                        $node->setType($part);
                        $part = '';
                        
                        $mode = 2; // Id
                    } else if($char == '/') {
                        $node->setType($part);
                        $part = '';
                        
                        $this->_nodes[] = $node;
                        $node = new EntityLocatorNode();
                        
                        $mode = 0; // Location
                    } else if(preg_match('/[a-zA-Z0-9-_]/', $char)) {
                        $part .= $char;
                    } else {
                        throw new InvalidArgumentException(
                            'Unexpected char: '.$char.' in locator: '.$path.' at char: '.$i
                        );
                    }
                    
                    break;
                    
                // Entity id
                case 2:
                    if($char == '"') {
                        $mode = 3;
                    } else if($char == '/') {
                        $mode = 0; // Location
                        $node->setId($part);
                        $part = '';
                        
                        $this->_nodes[] = $node;
                        $node = new EntityLocatorNode();
                    } else if(ctype_alnum($char)) {
                        $part .= $char;
                    } else {
                        throw new InvalidArgumentException(
                            'Unexpected char: '.$char.' in locator: '.$path.' at char: '.$i
                        );
                    }
                    
                    break;
                    
                // Entity id quote
                case 3:
                    if($char == '\\') {
                        $mode = 4; // Escape
                    } else if($char == '"') {
                        $mode = 5; // End quote
                    } else {
                        $part .= $char;
                    }
                    
                    break;
                    
                // Entity id escape
                case 4:
                    $part .= $char;
                    $mode = 3; // Quote
                    break;
                    
                // Entity id end quote
                case 5:
                    if($char != '/') {
                        throw new InvalidArgumentException(
                            'Unexpected char: '.$char.' in locator: '.$path.' at char: '.$i
                        );
                    }
                    
                    $mode = 0; // Location
                    $node->setId($part);
                    $part = '';
                    
                    $this->_nodes[] = $node;
                    $node = new EntityLocatorNode();
                    
                    break;
            }
            
        }
        
        if(empty($this->_nodes)) {
            throw new InvalidArgumentException(
                'No entity type definition detected in: '.$path
            );
        } else if($mode != 0) {
            throw new InvalidArgumentException(
                'Unexpected end of locator: '.$path
            );
        }
    }

    public function getScheme() {
        return $this->_scheme;
    }
    
    public function getNodes() {
        return $this->_nodes;
    }
    
    public function getFirstNode() {
        if(isset($this->_nodes[0])) {
            return $this->_nodes[0];
        }
        
        return null;
    }
    
    public function getFirstNodeType() {
        if(isset($this->_nodes[0])) {
            return $this->_nodes[0]->getType();
        }
        
        return null;
    }
    
    public function getFirstNodeId() {
        if(isset($this->_nodes[0])) {
            return $this->_nodes[0]->getId();
        }
        
        return null;
    }
    
    public function toString() {
        $output = $this->_scheme.'://';
        $nodes = array();
        
        foreach($this->_nodes as $node) {
            $nodes[] = $node->toString();
        }
        
        $output .= implode('/', $nodes);
        return $output;
    }
    
    public function __toString() {
        try {
            return (string)$this->toString();
        } catch(\Exception $e) {
            return '';
        }
    }
    
    public function toStringUpTo($type) {
        if($type instanceof IEntityLocatorNode) {
            $type = $type->getType();
        }
        
        $output = $this->_scheme.'://';
        $nodes = array();
        
        foreach($this->_nodes as $node) {
            $nodes[] = $node->toString();
            
            if($node->getType() == $type) {
                break;
            }
        }
        
        $output .= implode('/', $nodes);
        return $output;
    }
    
// Dump
    public function getDumpProperties() {
        return $this->toString();
    }
}