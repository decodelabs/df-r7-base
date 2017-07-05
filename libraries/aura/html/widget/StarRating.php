<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;
use df\arch;

class StarRating extends Base {

    const PRIMARY_TAG = 'div.starRating';

    protected $_value;
    protected $_max = 5;

    public function __construct(arch\IContext $context, $value, $max=5) {
        parent::__construct($context);

        $this->setMax($max);
        $this->setValue($value);
    }

    protected function _render() {
        $tag = $this->getTag();
        $tag->setDataAttribute('value', $this->_value);
        $tag->setDataAttribute('max', $this->_max);
        $tag->setTitle($this->_context->format->number($this->_value));

        $stars = [];

        for($i = 1; $i <= $this->_max; $i++) {
            if($i <= $this->_value) {
                $class = 'star';
            } else if($i > $this->_value && $i - 1 < $this->_value) {
                $class = 'star-half';
            } else {
                $class = 'star-empty';
            }

            $stars[] = $this->_context->html->icon($class);
        }

        return $tag->renderWith($stars);
    }

    public function setValue($value) {
        $value = (float)$value;

        if($value < 0) {
            $value = 0;
        }

        $this->_value = $value;
        return $this;
    }

    public function getValue() {
        return $this->_value;
    }

    public function setMax($max) {
        $this->_max = (int)$max;
        return $this;
    }

    public function getMax() {
        return $this->_max;
    }
}
