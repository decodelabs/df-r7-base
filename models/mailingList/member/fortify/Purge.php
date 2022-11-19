<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\mailingList\member\fortify;

use df\axis;

class Purge extends axis\fortify\Base
{
    protected function execute()
    {
        $total = 0;

        while (true) {
            $items = $this->_unit->select('id')

                ->where('date', '<', '-2 weeks')

                ->limit(100)
                ->toArray();

            if (empty($items)) {
                break;
            }

            foreach ($items as $item) {
                $total += $this->_unit->delete()
                    ->where('id', '=', $item['id'])
                    ->execute();
            }

            usleep(10000);
        }

        yield $total . ' removed';
    }
}
