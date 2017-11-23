<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\accessPass\fortify;

use df;
use df\core;
use df\apex;
use df\axis;

class PurgeExpired extends axis\fortify\Base {

    protected function execute() {
        $count = $this->data->user->accessPass->delete()
            ->where('expiryDate', '<', '-1 hour')
            ->execute();

        yield $count.' deleted';
    }
}
