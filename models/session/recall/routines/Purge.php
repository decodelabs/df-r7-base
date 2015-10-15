<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\session\recall\routines;

use df;
use df\core;
use df\apex;
use df\axis;

class Purge extends axis\routine\Consistency {

    protected function _execute() {
        $this->io->write('Purging recall keys...');
        $unit = $this->_unit;

        $count = $this->_unit->delete()
            ->where('date', '<', $unit::PURGE_THRESHOLD)
            ->execute();

        $this->io->writeLine(' '.$count.' found');
    }
}