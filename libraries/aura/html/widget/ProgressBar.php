<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df\arch;

class ProgressBar extends Base
{
    public const PRIMARY_TAG = 'progress';

    protected $_value;
    protected $_min;
    protected $_max;

    public function __construct(arch\IContext $context, $value, $max = 100, $min = 0)
    {
        parent::__construct($context);

        $this->setValue($value);
        $this->setMax($max);
        $this->setMin($min);
    }

    protected function _render()
    {
        $tag = $this->getTag();

        $tag->setAttribute('value', $this->_value);
        $tag->setAttribute('min', $this->_min);
        $tag->setAttribute('max', $this->_max);

        return $tag->render();
    }

    public function setValue($value)
    {
        $this->_value = $value;
        return $this;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function setMin($min)
    {
        $this->_min = $min;
        return $this;
    }

    public function getMin()
    {
        return $this->_min;
    }

    public function setMax($max)
    {
        $this->_max = $max;
        return $this;
    }

    public function getMax()
    {
        return $this->_max;
    }
}
