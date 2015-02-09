<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\models\user\client\routines;

use df;
use df\core;
use df\apex;
use df\axis;

class FixNames extends axis\routine\Consistency {
    
    protected function _execute() {
        $this->io->write('Tidying user names...');
        $count = 0;
        $list = $this->_unit->fetch()
            ->where('fullName', 'begins', ' ')
            ->orWhere('fullName', 'ends', ' ')
            ->orWhere('nickName', '=', '')
            ->orWhere('nickName', '=', ' ')
            ->isUnbuffered(true);

        foreach($list as $client) {
            $client['fullName'] = trim($client['fullName']);

            if(!strlen(trim($client['fullName']))) {
                $client['fullName'] = $this->format->firstName($client['fullName']);
            }

            if($client->hasChanged()) {
                $client->shouldBypassHooks(true);
                $client->save();
                $count++;
            }
        }

        $this->io->writeLine(' '.$count.' updated');
    }
}