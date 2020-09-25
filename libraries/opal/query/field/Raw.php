<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\field;

use df;
use df\core;
use df\opal;

use DecodeLabs\Glitch\Dumpable;

class Raw implements opal\query\IRawField, Dumpable
{
    use opal\query\TField;

    protected $_expression;
    protected $_alias;
    protected $_source;

    public function __construct(opal\query\ISource $source, string $expression, $alias=null)
    {
        if ($alias === null) {
            $alias = uniqid('expr');
        }

        $this->_expression = $expression;
        $this->_alias = $alias;
        $this->_source = $source;
    }

    public function getSource()
    {
        return $this->_source;
    }

    public function getSourceAlias()
    {
        return $this->_source->getAlias();
    }

    public function getExpression(): string
    {
        return $this->_expression;
    }

    public function getName(): string
    {
        return $this->_expression;
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
        return $this->_alias !== null;
    }

    public function getQualifiedName()
    {
        return $this->getSourceAlias().'.'.$this->_alias;
    }

    public function dereference()
    {
        return [$this];
    }

    public function isOutputField()
    {
        return $this->_source->isOutputField($this);
    }

    public function rewriteAsDerived(opal\query\ISource $source)
    {
        return $this;
    }

    public function toString(): string
    {
        return '@raw '.$this->_expression;
    }
}
