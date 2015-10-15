<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\session\stub\routines;

use df;
use df\core;
use df\apex;
use df\axis;

class Purge extends axis\routine\Consistency {

    protected function _execute() {
        $this->io->write('Purging session stubs...');
        $count = $this->_unit->delete()
            ->where('date', '<', '-2 hours')
            ->execute();

        $this->io->writeLine(' '.$count.' found');
    }
}