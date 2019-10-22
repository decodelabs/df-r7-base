<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query\clause;

use df;
use df\core;
use df\opal;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class ListBase implements opal\query\IClauseList, Inspectable
{
    use opal\query\TQuery_NestedComponent;
    use core\lang\TChainable;

    protected $_isOr = false;
    protected $_clauses = [];
    protected $_parent;

    public function __construct(opal\query\IClauseFactory $parent, $isOr=false)
    {
        $this->_parent = $parent;
        $this->_isOr = (bool)$isOr;
    }

    public function getParent()
    {
        return $this->_parent;
    }

    public function getQuery()
    {
        $target = $this;

        while (!$target instanceof opal\query\IQuery && $target !== null) {
            $test = $target->getParent();

            if ($test === $target) {
                return null;
            }

            $target = $test;
        }

        return $target;
    }

    public function __clone()
    {
        foreach ($this->_clauses as $i => $clause) {
            $this->_clauses[$i] = clone $clause;
        }
    }

    public function toArray(): array
    {
        return $this->_clauses;
    }

    public function isOr(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_isOr = $flag;
            return $this;
        }

        return $this->_isOr;
    }

    public function isAnd(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_isOr = !$flag;
            return $this;
        }

        return !$this->_isOr;
    }

    public function _addClause(opal\query\IClauseProvider $clause=null)
    {
        if ($clause !== null) {
            $this->_clauses[] = $clause;
        }

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->_clauses);
    }

    public function count()
    {
        return count($this->_clauses);
    }

    public function getSourceManager()
    {
        return $this->_parent->getSourceManager();
    }

    public function getSource()
    {
        return $this->_parent->getSource();
    }

    public function getSourceAlias()
    {
        return $this->_parent->getSourceAlias();
    }

    public function referencesSourceAliases(array $sourceAliases)
    {
        foreach ($this->_clauses as $clause) {
            if ($clause->referencesSourceAliases($sourceAliases)) {
                return true;
            }
        }

        return false;
    }

    public function getNonLocalFieldReferences()
    {
        $output = [];

        foreach ($this->_clauses as $clause) {
            $output = array_merge($output, $clause->getNonLocalFieldReferences());
        }

        return $output;
    }

    public function rewriteAsDerived(opal\query\ISource $source)
    {
        foreach ($this->_clauses as $clause) {
            $clause->rewriteAsDerived($source);
        }

        return $this;
    }

    public function getClausesFor(opal\query\ISource $source, opal\query\IClauseFactory $parent=null)
    {
        if ($parent === null) {
            $parent = $this->_parent;
        }

        $class = get_class($this);
        $output = new $class($parent);

        foreach ($this->_clauses as $clause) {
            if ($clause instanceof opal\query\IClauseList) {
                if (null !== ($newClause = $clause->getClausesFor($source, $output))) {
                    $output->_clauses[] = $newClause;
                }
            } else {
                if ($source->getAlias() == $clause->getField()->getSource()->getAlias()) {
                    $output->_clauses[] = $clause;
                } elseif ($clause->isOr()) {
                    return null;
                }
            }
        }

        if (empty($output->_clauses)) {
            return null;
        }

        return $output;
    }

    public function extractClausesFor(opal\query\ISource $source, $checkValues=true)
    {
        $output = [];
        $sourceAlias = $source->getAlias();

        foreach ($this->_clauses as $clause) {
            if ($clause instanceof opal\query\IClauseList) {
                $output = array_merge($output, $clause->extractClausesFor($source));
                continue;
            }

            if ($clause->getField()->getSource()->getAlias() == $sourceAlias) {
                $output[] = $clause;
                continue;
            }

            if ($checkValues) {
                $value = $clause->getValue();

                if ($value instanceof opal\query\IField
                && $value->getSource()->getAlias() == $sourceAlias) {
                    $output[] = $clause;
                }
            }
        }

        return $output;
    }

    public function isLocalTo(array $sources)
    {
        $source = $this->getSource();

        if (!isset($sources[$source->getUniqueId()])) {
            return false;
        }

        foreach ($this->_clauses as $clause) {
            if ($clause instanceof opal\query\IClauseList) {
                if (!$clause->isLocalTo($sources)) {
                    return false;
                }
            } else {
                $source = $clause->getField()->getSource();

                if (!isset($sources[$source->getUniqueId()])) {
                    return false;
                }
                $value = $clause->getValue();

                if ($value instanceof opal\query\IField) {
                    $source = $value->getSource();

                    if (!isset($sources[$source->getUniqueId()])) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function clear()
    {
        $this->_clauses = [];
        return $this;
    }

    public function endClause()
    {
        return $this->getNestedParent();
    }

    public function getLocalFieldList(): array
    {
        $output = [];

        foreach ($this->_clauses as $clause) {
            if ($clause instanceof opal\query\IClauseList) {
                $output = array_merge($output, $clause->getLocalFieldList());
            } else {
                $field = $clause->getField();
                $output[$field->getQualifiedName()] = $field;
            }
        }

        return $output;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        if ($this->_parent instanceof opal\query\IClauseList) {
            if ($this->_isOr) {
                $type = 'OR';
            } else {
                $type = 'AND';
            }

            $entity->setProperty('*type', $inspector($type));
        }

        $entity
            ->setValues($inspector->inspectList($this->_clauses))
            ->setShowKeys(false);
    }
}
