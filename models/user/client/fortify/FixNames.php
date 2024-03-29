<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\models\user\client\fortify;

use DecodeLabs\Dictum;

use df\axis;

class FixNames extends axis\fortify\Base
{
    protected function execute()
    {
        $count = 0;
        $list = $this->_unit->fetch()
            ->where('fullName', 'begins', ' ')
            ->orWhere('fullName', 'ends', ' ')
            ->orWhere('nickName', '=', '')
            ->orWhere('nickName', '=', ' ')
            ->isUnbuffered(true);

        foreach ($list as $client) {
            $client['fullName'] = trim((string)$client['fullName']);

            if (!strlen(trim($client['nickName']))) {
                $client['nickName'] = Dictum::firstName($client['fullName']);
            }

            if ($client->hasChanged()) {
                $client->shouldBypassHooks(true);
                $client->save();
                $count++;
            }
        }

        yield $count . ' updated';
    }
}
