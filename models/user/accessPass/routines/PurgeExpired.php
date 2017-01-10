<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\accessPass\routines;

use df;
use df\core;
use df\apex;
use df\axis;

class PurgeExpired extends axis\routine\Consistency {

    protected function _execute() {
        $this->io->write('Purging expired passes...');

        $count = $this->data->user->accessPass->delete()
            ->where('expiryDate', '<', '-1 hour')
            ->execute();

        $this->io->writeLine(' '.$count.' deleted');
    }
}