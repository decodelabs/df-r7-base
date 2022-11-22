<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\query\field;

use DecodeLabs\Glitch\Dumpable;

use df\opal;

class Virtual implements opal\query\IVirtualField, Dumpable
{
    use opal\query\TField;

    protected $_name;
    protected $_alias;
    protected $_targetFields = [];
    protected $_source;
    protected $_targetSourceAlias;

    public function __construct(opal\query\ISource $source, string $name, $alias = null, array $targetFields = [])
    {
        $this->_source = $source;
        $this->_name = $name;

        if ($alias === null) {
            $alias = $name;
        }

        $this->_alias = $alias;
        $this->_targetFields = $targetFields;
    }


    public function getSource()
    {
        return $this->_source;
    }

    public function getSourceAlias()
    {
        return $this->_source->getAlias();
    }

    public function getQualifiedName()
    {
        return $this->getSourceAlias() . '.' . $this->getName();
    }

    public function getName(): string
    {
        return $this->_name;
    }

    public function setAlias($alias)
    {
        $this->_alias = $alias;
        return $this;
    }

    public function getAlias()
    {
        return $this->_alias;
    }

    public function hasDiscreetAlias()
    {
        return $this->getAlias() !== $this->getName();
    }

    public function setTargetSourceAlias($alias)
    {
        $this->_targetSourceAlias = $alias;
        return $this;
    }

    public function getTargetSourceAlias()
    {
        return $this->_targetSourceAlias;
    }


    public function getTargetFields()
    {
        return $this->_targetFields;
    }

    public function dereference()
    {
        $output = [];

        foreach ($this->_targetFields as $key => $field) {
            if ($field instanceof opal\query\IVirtualField) {
                $output = array_merge($output, $field->dereference());
            } else {
                $output[$key] = $field;
            }
        }

        return $output;
    }

    public function isOutputField()
    {
        return $this->_source->isOutputField($this);
    }

    public function rewriteAsDerived(opal\query\ISource $source)
    {
        return new self($source, $this->_name, $this->_alias, $this->_targetFields);

        /*
        $targetFields = [];

        foreach ($this->_targetFields as $field) {
            $targetFields[] = $field->rewriteAsDerived($source);
        }

        return new self($source, $this->_source->getAlias().'.'.$this->_name, $this->_source->getAlias().'.'.$this->_alias, $targetFields);
         */
    }

    public function toString(): string
    {
        $output = $this->getQualifiedName();

        if ($this->hasDiscreetAlias()) {
            $output .= ' as ' . $this->getAlias();
        }

        if (!empty($this->_targetFields)) {
            $targets = [];

            foreach ($this->_targetFields as $target) {
                $targets[] = $target->getQualifiedName();
            }

            $output .= '[' . implode(',', $targets) . ']';
        }

        return $output;
    }
}
