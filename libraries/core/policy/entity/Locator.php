<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\policy\entity;

use df;
use df\core;

class Locator implements core\policy\IEntityLocator, core\IDumpable {
    
    protected $_scheme;
    protected $_nodes = array();
    
    public static function factory($locator) {
        if($locator instanceof core\policy\IEntityLocator) {
            return $locator;
        }

        if($locator instanceof core\policy\IEntityLocatorProvider) {
            return $locator->getEntityLocator();
        }
        
        return new self($locator);
    }

    public static function domainFactory($domain, $id=null) {
        $output = self::factory($domain);

        if($id !== null) {
            $output->setId($id);
        }

        return $output;
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
    

    // Format:
    // handler://[path/to/]Entity[:id][/[path/to/]SubEntity[:id]]]
    private function _splitEntityPath($path) {
        $path = trim($path, '/').'/';
        $length = strlen($path);
        $mode = 0;
        $part = '';
        
        $node = new LocatorNode();
        
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
                        throw new core\policy\InvalidArgumentException(
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
                        $node = new LocatorNode();
                        
                        $mode = 0; // Location
                    } else if(preg_match('/[a-zA-Z0-9-_]/', $char)) {
                        $part .= $char;
                    } else {
                        throw new core\policy\InvalidArgumentException(
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
                        $node = new LocatorNode();
                    } else if(ctype_alnum($char)) {
                        $part .= $char;
                    } else {
                        throw new core\policy\InvalidArgumentException(
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
                        throw new core\policy\InvalidArgumentException(
                            'Unexpected char: '.$char.' in locator: '.$path.' at char: '.$i
                        );
                    }
                    
                    $mode = 0; // Location
                    $node->setId($part);
                    $part = '';
                    
                    $this->_nodes[] = $node;
                    $node = new LocatorNode();
                    
                    break;
            }
            
        }
        
        if(empty($this->_nodes)) {
            throw new core\policy\InvalidArgumentException(
                'No entity type definition detected in: '.$path
            );
        } else if($mode != 0) {
            throw new core\policy\InvalidArgumentException(
                'Unexpected end of locator: '.$path
            );
        }
    }

    public function getEntityLocator() {
        return $this;
    }


// Scheme
    public function setScheme($scheme) {
        $this->_scheme = $scheme;
        return $this;
    }
    
    public function getScheme() {
        return $this->_scheme;
    }
    


// Nodes
    public function setNodes(array $nodes) {
        $this->_nodes = array();
        return $this->addNodes($nodes);
    }

    public function addNodes(array $nodes) {
        foreach($nodes as $node) {
            if(!$node instanceof core\policy\IEntityLocatorNode) {
                throw new core\policy\InvalidArgumentException(
                    'Nodes much implement IEntityLocatorNode'
                );
            }

            $this->addNode($node);
        }

        return $this;
    }

    public function addNode(core\policy\IEntityLocatorNode $node) {
        $this->_nodes[] = $node;
        return $this;
    }

    public function getNode($index) {
        $index = (int)$index;
        
        if($index < 0) {
            $index += count($this->_nodes);
            
            if($index < 0) {
                return null;
            }
        }
        
        if(isset($this->_nodes[$index])) {
            return $this->_nodes[$index];
        }
        
        return null;
    }

    public function getNodeType($index) {
        if($node = $this->getNode($index)) {
            return $node->getType();
        }
    }

    public function getNodeId($index) {
        if($node = $this->getNode($index)) {
            return $node->getId();
        }
    }

    public function hasNode($index) {
        $index = (int)$index;
        
        if($index < 0) {
            $index += count($this->_nodes);
            
            if($index < 0) {
                return false;
            }
        }
        
        return isset($this->_nodes[$index]);
    }

    public function removeNode($index) {
        $index = (int)$index;
        
        if($index < 0) {
            $index += count($this->_nodes);
            
            if($index < 0) {
                return $this;
            }
        }
        
        unset($this->_nodes[$index]);
        $this->_nodes = array_values($this->_nodes);
        return $this;
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

    public function getLastNode() {
        $i = count($this->_nodes) - 1;

        if(isset($this->_nodes[$i])) {
            return $this->_nodes[$i];
        }

        return null;
    }

    public function getLastNodeType() {
        if($node = $this->getLastNode()) {
            return $node->getType();
        }
    }

    public function getLastNodeId() {
        if($node = $this->getLastNode()) {
            return $node->getId();
        }
    }

    public function getDomain() {
        $nodes = $this->_nodes;
        $last = clone array_pop($nodes);

        foreach($nodes as $i => $node) {
            $nodes[$i] = $node->toString();
        }

        $last->setId(null);
        $nodes[] = $last->toString();

        return $this->_scheme.'://'.implode('/', $nodes);
    }

    public function setId($id) {
        if($node = $this->getLastNode()) {
            $node->setId($id);
        }

        return $this;
    }

    public function getId() {
        if($node = $this->getLastNode()) {
            return $node->getId();
        }
    }
    
    public function toString() {
        $nodes = array();
        
        foreach($this->_nodes as $node) {
            $nodes[] = $node->toString();
        }
        
        return $this->_scheme.'://'.implode('/', $nodes);
    }
    
    public function __toString() {
        try {
            return (string)$this->toString();
        } catch(\Exception $e) {
            return '';
        }
    }
    
    public function toStringUpTo($type) {
        if($type instanceof core\policy\IEntityLocatorNode) {
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