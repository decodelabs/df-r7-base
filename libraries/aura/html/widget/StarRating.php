<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use DecodeLabs\Dictum;

use df\arch;

class StarRating extends Base
{
    public const PRIMARY_TAG = 'div.starRating';

    protected $_value;
    protected $_max = 5;

    public function __construct(arch\IContext $context, $value, $max = 5)
    {
        parent::__construct($context);

        $this->setMax($max);
        $this->setValue($value);
    }

    protected function _render()
    {
        $tag = $this->getTag();
        $tag->setDataAttribute('value', $this->_value);
        $tag->setDataAttribute('max', $this->_max);
        $tag->setTitle(Dictum::$number->format($this->_value));

        $stars = [];

        for ($i = 1; $i <= $this->_max; $i++) {
            if ($this->_value >= $i - 0.25) {
                $class = 'star';
            } elseif (
                $this->_value >= $i - 0.75 &&
                $this->_value < $i - 0.25
            ) {
                $class = 'star-half';
            } else {
                $class = 'star-empty';
            }

            $stars[] = $this->_context->html->icon($class);
        }

        return $tag->renderWith($stars);
    }

    public function setValue($value)
    {
        $value = (float)$value;

        if ($value < 0) {
            $value = 0;
        }

        $this->_value = $value;
        return $this;
    }

    public function getValue()
    {
        return $this->_value;
    }

    public function setMax($max)
    {
        $this->_max = (int)$max;
        return $this;
    }

    public function getMax()
    {
        return $this->_max;
    }
}
