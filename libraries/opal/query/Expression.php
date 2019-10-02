<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\opal\query;

use df;
use df\core;
use df\opal;

use DecodeLabs\Glitch\Inspectable;
use DecodeLabs\Glitch\Dumper\Entity;
use DecodeLabs\Glitch\Dumper\Inspector;

class Expression implements IExpression, Inspectable
{
    protected $_field;
    protected $_elements = [];
    protected $_parentExpression;
    protected $_parentQuery;

    private $_isExpectingValue = true;

    public function __construct(IDataUpdateQuery $parentQuery, $field, array $elements, IExpression $parentExpression=null)
    {
        $this->_parentQuery = $parentQuery;
        $this->_parentExpression = $parentExpression;
        $this->_field = $field;

        if (!empty($elements)) {
            $this->express(...$elements);
        }
    }

    public function getParentQuery()
    {
        return $this->_parent;
    }

    public function getParentExpression()
    {
        return $this->_parentExpression;
    }

    public function getElements()
    {
        return $this->_elements;
    }

    public function op($operator)
    {
        if ($this->_isExpectingValue) {
            throw new LogicException(
                'Found operator when expecting reference or value'
            );
        }

        switch ($operator) {
            case IExpressionOperator::ADD:
            case IExpressionOperator::SUBTRACT:
            case IExpressionOperator::MULTIPLY:
            case IExpressionOperator::DIVIDE:
            case IExpressionOperator::MOD:
            case IExpressionOperator::POWER:
                $this->_elements[] = new Expression_Operator($operator);
                $this->_isExpectingValue = true;
                break;

            default:
                throw new InvalidArgumentException(
                    'Unknown operator: '.$operator
                );
        }

        return $this;
    }

    public function group(...$elements)
    {
        return $this->beginExpression($elements)->endExpression();
    }

    public function express(...$elements)
    {
        $source = $this->_parentQuery->getSource();
        $sourceManager = $this->_parentQuery->getSourceManager();

        foreach ($elements as $element) {
            if ($this->_isExpectingValue) {
                $this->_elements[] = $this->_processElement($sourceManager, $source, $element);
                $this->_isExpectingValue = false;
            } else {
                $this->op($element);
            }
        }

        return $this;
    }

    protected function _processElement(ISourceManager $sourceManager, ISource $source, $element)
    {
        if (is_string($element)) {
            if (preg_match('/^[\"\']([^\"\'])+[\"\']$/', $element)) {
                return new Expression_Value(substr($element, 1, -1));
            }

            if (!$field = $sourceManager->extrapolateIntrinsicField($source, $element)) {
                throw new InvalidArgumentException(
                    'Cound not extract reference or value from: '.$element
                );
            }

            $element = $field;
        }

        if (is_scalar($element)) {
            return new Expression_Value($element);
        } elseif ($element instanceof opal\query\IIntrinsicField || $element instanceof IExpressionValue) {
            return $element;
        } elseif ($element instanceof opal\query\IVirtualField) {
            $inner = $element->dereference();

            if (count($inner) > 1) {
                throw new LogicException(
                    'Cannot use multi-primitive virtual fields in expressions... yet :)'
                );
            }

            return array_shift($inner);
        } elseif ($element instanceof IExpression) {
            if ($element->isExpectingValue()) {
                throw new InvalidArgumentException(
                    'Cannot add sub expression - it is still expecting a reference or value'
                );
            }

            return $element;
        }

        throw new InvalidArgumentException(
            'Count not extract reference or value from element'
        );
    }

    public function beginExpression(...$elements)
    {
        return new self($this->_parentQuery, $this->_field, $elements, $this);
    }

    public function correlate($targetField)
    {
        Glitch::incomplete($targetField);
    }

    public function isExpectingValue()
    {
        return $this->_isExpectingValue;
    }

    public function addExpression(IExpression $expression)
    {
        if (!$this->_isExpectingValue) {
            throw new LogicException(
                'Cannot add sub expression - expecting an operator'
            );
        }

        if ($expression->isExpectingValue()) {
            throw new InvalidArgumentException(
                'Cannot add sub expression - it is still expecting a reference or value'
            );
        }

        $this->_elements[] = $expression;
        $this->_isExpectingValue = false;
        return $this;
    }

    public function endExpression()
    {
        if ($this->_isExpectingValue) {
            throw new LogicException(
                'Cannot end expression - expecting reference or value'
            );
        }

        if ($this->_parentExpression) {
            return $this->_parentExpression->addExpression($this);
        } else {
            $this->_parentQuery->set($this->_field, $this);
            return $this->_parentQuery;
        }
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setValues($inspector->inspectList($this->_elements));
    }
}


class Expression_Operator implements IExpressionOperator, Inspectable
{
    public $operator;

    public function __construct($operator)
    {
        $this->operator = $operator;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setText($this->operator);
    }
}

class Expression_Value implements IExpressionValue, Inspectable
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setValues([$inspector($this->value)])
            ->setShowKeys(false);
    }
}
