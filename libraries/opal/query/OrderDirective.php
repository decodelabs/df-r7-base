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

class OrderDirective implements IOrderDirective, Inspectable
{
    use core\TStringProvider;

    protected $_isDescending = false;
    protected $_nullOrder = 'ascending';
    protected $_field;

    public function __construct(opal\query\IField $field, $direction=null)
    {
        $this->setField($field);
        $this->setDirection($direction);
    }

    public function setField(opal\query\IField $field)
    {
        $this->_field = $field;
        return $this;
    }

    public function getField()
    {
        return $this->_field;
    }

    public function isFieldNullable()
    {
        if ($this->_field instanceof opal\query\IIntrinsicField) {
            $source = $this->_field->getSource();

            if (!$source->isPrimary()) {
                return true;
            }

            if ($processor = $source->getFieldProcessor($this->_field)) {
                return $processor->canReturnNull();
            }

            return null;
        } elseif ($this->_field instanceof opal\query\ISearchController) {
            return false;
        } else {
            return null;
        }
    }

    public function setDirection($direction)
    {
        if (is_string($direction)) {
            if (!ctype_alpha(substr($direction, -1))) {
                $modifier = substr($direction, -1);
                $direction = substr($direction, 0, -1);
            } else {
                $modifier = null;
            }

            switch (strtolower($direction)) {
                case 'desc':
                case 'd':
                    $direction = true;
                    break;

                default:
                    $direction = false;
            }

            switch ($modifier) {
                case '!':
                    $this->setNullOrder('last');
                    break;

                case '^':
                    $this->setNullOrder('first');
                    break;

                case '*':
                    $this->setNullOrder('descending');
                    break;
            }
        }

        $this->_isDescending = (bool)$direction;
        return $this;
    }

    public function getDirection()
    {
        if ($this->_isDescending) {
            $output = 'DESC';
        } else {
            $output = 'ASC';
        }

        switch ($this->_nullOrder) {
            case 'last':
                $output .= '!';
                break;

            case 'first':
                $output .= '^';
                break;

            case 'descending':
                $output .= '*';
                break;
        }

        return $output;
    }

    public function getReversedDirection()
    {
        if ($this->_isDescending) {
            $output = 'ASC';
        } else {
            $output = 'DESC';
        }

        switch ($this->_nullOrder) {
            case 'last':
                $output .= '!';
                break;

            case 'first':
                $output .= '^';
                break;

            case 'descending':
                $output .= '*';
                break;
        }

        return $output;
    }

    public function isDescending(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_isDescending = $flag;
            return $this;
        }

        return $this->_isDescending;
    }

    public function isAscending(bool $flag=null)
    {
        if ($flag !== null) {
            $this->_isDescending = !$flag;
            return $this;
        }

        return !$this->_isDescending;
    }

    public function setNullOrder($order)
    {
        $this->_nullOrder = NullOrder::normalize($order);
        return $this;
    }

    public function getNullOrder()
    {
        return $this->_nullOrder;
    }

    public function toString(): string
    {
        return $this->_field->getQualifiedName().' '.$this->getDirection();
    }

    /**
     * Inspect for Glitch
     */
    public function glitchInspect(Entity $entity, Inspector $inspector): void
    {
        $entity->setDefinition($this->toString());
    }
}
