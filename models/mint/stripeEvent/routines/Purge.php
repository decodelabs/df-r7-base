<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mint\stripeEvent\routines;

use df;
use df\core;
use df\apex;
use df\axis;

class Purge extends axis\routine\Consistency {

    const DURATION = '-2 months';

    protected function _execute() {
        $this->io->write('Purging stripe event logs...');

        $count = $this->data->mint->stripeEvent->delete()
            ->where('date', '<', self::DURATION)
            ->execute();

        $this->io->writeLine(' '.$count.' removed');
    }
}