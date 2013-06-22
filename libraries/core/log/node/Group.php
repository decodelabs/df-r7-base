<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\log\node;

use df;
use df\core;

class Group implements core\log\IGroupNode {
    
    use core\debug\TLocationProvider;
    use core\log\TEntryPoint;
    
    protected $_title;
    protected $_children = array();
    
    public function __construct($title=null, $file=null, $line=null) {
        $this->setNodeTitle($title);
        $this->_file = $file;
        $this->_line = $line;
    }
    
    public function setNodeTitle($title) {
        if(empty($title)) {
            $title = 'Group';
        }
        
        $this->_title = $title;
        return $this;
    }
    
    public function getNodeTitle() {
        return $this->_title;
    }
    
    public function getNodeType() {
        return 'group';
    }
    
    public function isCritical() {
        foreach($this->_children as $node) {
            if($node->isCritical()) {
                return true;
            }
        }
        
        return false;
    }
    
    
// Children
    public function toArray() {
        return $this->_children;
    }

    public function addChild(core\log\INode $node) {
        return $this->addNode($node);
    }
    
    public function addNode(core\log\INode $node) {
        $this->_children[] = $node;
        return $this;
    }
    
    public function getChildren() {
        return $this->_children;
    }
    
    public function hasChildren() {
        return !empty($this->_children);
    }
    
    public function clearChildren() {
        $this->_children = array();
        return $this;
    }
    

// Nodes
    public function getNodeCounts() {
        $output = [
            'info' => 0,
            'todo' => 0,
            'warning' => 0,
            'error' => 0,
            'deprecated' => 0,
            'stub' => 0,
            'dump' => 0,
            'exception' => 0,
            'stackTrace' => 0,
            'group' => 0
        ];
        
        $this->_countNodes($this, $output);
        return $output;
    }
    
    private function _countNodes(core\log\IGroupNode $node, &$counts) {
        foreach($node->getChildren() as $child) {
            $counts[$child->getNodeType()]++;
            
            if($child instanceof core\log\IGroupNode) {
                $this->_countNodes($child, $counts);
            }
        }
    }
}