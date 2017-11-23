<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\session\recall\fortify;

use df;
use df\core;
use df\apex;
use df\axis;

class Purge extends axis\fortify\Base {

    protected function execute() {
        $unit = $this->_unit;

        $count = $this->_unit->delete()
            ->where('date', '<', $unit::PURGE_THRESHOLD)
            ->execute();

        yield $count.' removed';
    }
}
