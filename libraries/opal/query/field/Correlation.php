<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\opal\query\field;

use DecodeLabs\Glitch;

use DecodeLabs\Glitch\Dumpable;
use df\opal;

class Correlation implements opal\query\ICorrelationField, Dumpable
{
    use opal\query\TField;

    protected $_query;

    public function __construct(opal\query\ICorrelationQuery $query)
    {
        $this->_query = $query;
    }

    public function getSource()
    {
        return $this->_query->getSource();
    }

    public function getSourceAlias()
    {
        return $this->_query->getSourceAlias();
    }

    public function getCorrelationQuery()
    {
        return $this->_query;
    }

    public function getQualifiedName()
    {
        return $this->_query->getFieldAlias();
        //return 'CORRELATION('.$this->getSourceAlias().'.'.$this->getAlias().')';
    }

    public function getAggregateOutputField()
    {
        foreach ($this->_query->getSource()->getOutputFields() as $field) {
            if ($field instanceof opal\query\IAggregateField) {
                return $field;
            }
        }
    }

    public function getName(): string
    {
        return $this->getAlias();
    }

    public function setAlias($alias)
    {
        Glitch::incomplete($alias);
    }

    public function getAlias()
    {
        return $this->_query->getFieldAlias();
    }

    public function hasDiscreetAlias()
    {
        return false;
    }

    public function dereference()
    {
        return [$this];
    }

    public function isOutputField()
    {
        return true;
    }

    public function rewriteAsDerived(opal\query\ISource $source)
    {
        Glitch::incomplete($source);
    }

    public function toString(): string
    {
        return $this->getAlias() . '()';
    }

    /**
     * Export for dump inspection
     */
    public function glitchDump(): iterable
    {
        yield 'property:' . $this->getAlias() => $this->_query;
    }
}
