<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\iris\map\aspect;

use df;
use df\core;
use df\iris;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class TypeReference extends iris\map\Node implements iris\map\IAspect, Inspectable
{
    protected $_name;
    protected $_context = iris\processor\Type::CONTEXT_CLASS;
    protected $_namespace;

    public function __construct(EntityNamespace $namespace, $name, $context=iris\processor\Type::CONTEXT_CLASS)
    {
        parent::__construct($namespace);

        $this->setNamespace($namespace);
        $this->setName($name);
        $this->setContext($context);
    }

    public function setNamespace(EntityNamespace $namespace)
    {
        $this->_namespace = $namespace;
        return $this;
    }

    public function getNamespace()
    {
        return $this->_namespace;
    }

    public function setName($name)
    {
        $this->_name = $name;
        return $this;
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function setContext($context)
    {
        $this->_context = strtoupper(substr($context, 0, 1));
        return $this;
    }

    public function getContext()
    {
        return $this->_context;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setText($this->_namespace.'.'.$this->_context.'/'.$this->_name);
    }
}
