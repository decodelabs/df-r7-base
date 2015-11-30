<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\aura\html\widget;

use df\core;
use df\aura;
use df\arch;

class OrderedList extends BulletList implements IOrderedDataDrivenListWidget {

    use TWidget_OrderedDataDrivenList;

    const PRIMARY_TAG = 'ol';

    protected function _render() {
        $tag = $this->getTag();

        if($this->_start) {
            $tag->setAttribute('start', $this->_start);
        }

        if($this->_isReversed) {
            $tag->setAttribute('reversed', 'reversed');
        }

        return parent::_render();
    }
}
