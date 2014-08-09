<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df;
use df\core;
use df\aura;

class ButtonArea extends Container {

// Stacked
    public function isStacked($flag=null) {
        $tag = $this->getTag();

        if($flag !== null) {
            if((bool)$flag) {
                $tag->addClass('stacked');
            } else {
                $tag->removeClass('stacked');
            }

            return $this;
        }

        return $tag->hasClass('stacked');
    }
}
