<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\map;

use df;
use df\core;
use df\iris;
    
class NamespaceDeclaration extends Node implements core\IDumpable {

    protected $_namespace;
    protected $_namespaceShortcuts = [];
    protected $_typeShortcuts = [];

    public function __construct(iris\ILocationProvider $locationProvider, iris\map\aspect\EntityNamespace $namespace) {
        parent::__construct($locationProvider);
        $this->setNamespace($namespace);
    }

    public function setNamespace(iris\map\aspect\EntityNamespace $namespace) {
        $this->_namespace = $namespace;
        return $this;
    }

    public function getNamespace() {
        return $this->_namespace;
    }


// Namespace shortcuts
    public function addNamespaceShortcut($alias, iris\map\aspect\EntityNamespace $namespace) {
        $this->_namespaceShortcuts[$alias] = $namespace;
        return $this;
    }

    public function getNamespaceShortcut($alias) {
        if(isset($this->_namespaceShortcuts[$alias])) {
            return $this->_namespaceShortcuts[$alias];
        }
    }

    public function getNamespaceShortcuts() {
        return $this->_namespaceShortcuts;
    }


// Type shortcuts
    public function addTypeShortcut($alias, iris\map\aspect\TypeReference $type) {
        $this->_typeShortcuts[$alias] = $type;
        return $this;
    }

    public function getTypeShortcut($alias, $context=null) {
        $output = null;

        if(isset($this->_typeShortcuts[$alias])) {
            $output = $this->_typeShortcuts[$alias];

            if($context !== null && $output->getContext() != $context) {
                $output = null;
            }
        }

        return $output;
    }

    public function getTypeShortcuts() {
        return $this->_typeShortcuts;
    }


// Dump
    public function getDumpProperties() {
        $output = [
            'namespace' => (string)$this->_namespace
        ];

        if(!empty($this->_namespaceShortcuts)) {
            $output['namespaceShortcuts'] = $this->_namespaceShortcuts;
        }

        if(!empty($this->_typeShortcuts)) {
            $output['typeShortcuts'] = $this->_typeShortcuts;
        }

        if($this->_comment) {
            $output['comment'] = $this->_comment;
        }

        return $output;
    }
}