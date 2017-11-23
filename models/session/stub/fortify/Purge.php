<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\session\stub\fortify;

use df;
use df\core;
use df\apex;
use df\axis;

class Purge extends axis\fortify\Base {

    protected function execute() {
        $count = $this->_unit->delete()
            ->where('date', '<', '-2 hours')
            ->execute();

        yield $count.' removed';
    }
}
