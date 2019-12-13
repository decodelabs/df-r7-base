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

class Purge extends axis\fortify\Base
{
    protected function execute()
    {
        $total = 0;

        while (true) {
            $items = $this->_unit->select('user', 'key')

                ->where('date', '<', $this->_unit::PURGE_THRESHOLD)

                ->limit(100)
                ->toArray();

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $total += $this->_unit->delete()
                    ->where('user', '=', $item['user'])
                    ->where('key', '=', $item['key'])
                    ->execute();
            }

            usleep(10000);
        }

        yield $total.' removed';
    }
}
