<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\map;

use df;
use df\core;
use df\iris;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class NamespaceDeclaration extends Node implements Inspectable
{
    protected $_namespace;
    protected $_namespaceShortcuts = [];
    protected $_typeShortcuts = [];

    public function __construct(iris\ILocationProvider $locationProvider, iris\map\aspect\EntityNamespace $namespace)
    {
        parent::__construct($locationProvider);
        $this->setNamespace($namespace);
    }

    public function setNamespace(iris\map\aspect\EntityNamespace $namespace)
    {
        $this->_namespace = $namespace;
        return $this;
    }

    public function getNamespace()
    {
        return $this->_namespace;
    }


    // Namespace shortcuts
    public function addNamespaceShortcut($alias, iris\map\aspect\EntityNamespace $namespace)
    {
        $this->_namespaceShortcuts[$alias] = $namespace;
        return $this;
    }

    public function getNamespaceShortcut($alias)
    {
        if (isset($this->_namespaceShortcuts[$alias])) {
            return $this->_namespaceShortcuts[$alias];
        }
    }

    public function getNamespaceShortcuts()
    {
        return $this->_namespaceShortcuts;
    }


    // Type shortcuts
    public function addTypeShortcut($alias, iris\map\aspect\TypeReference $type)
    {
        $this->_typeShortcuts[$alias] = $type;
        return $this;
    }

    public function getTypeShortcut($alias, $context=null)
    {
        $output = null;

        if (isset($this->_typeShortcuts[$alias])) {
            $output = $this->_typeShortcuts[$alias];

            if ($context !== null && $output->getContext() != $context) {
                $output = null;
            }
        }

        return $output;
    }

    public function getTypeShortcuts()
    {
        return $this->_typeShortcuts;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setProperty('namespace', $inspector($this->_namespace));

        if (!empty($this->_namespaceShortcuts)) {
            $entity->setProperty('namespaceShortcuts', $inspector($this->_namespaceShortcuts));
        }

        if (!empty($this->_typeShortcuts)) {
            $entity->setProperty('typeShortcuts', $inspector($this->_typeShortcuts));
        }

        if ($this->_comment) {
            $entity->setProperty('comment', $inspector($this->_comment));
        }
    }
}
