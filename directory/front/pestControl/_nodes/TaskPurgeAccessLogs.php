<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\pestControl\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskPurgeAccessLogs extends arch\node\Task
{
    const MAX_LOOP = 250;
    
    public function execute()
    {
        $threshold = '-'.$this->data->pestControl->getPurgeThreshold();
        $loop = $total = 0;

        while (++$loop < self::MAX_LOOP) {
            $total += $count = $this->data->pestControl->accessLog->delete()
                ->where('isArchived', '=', false)
                ->where('date', '<', $threshold)
                ->limit(100)
                ->execute();

            if (!$count) {
                break;
            }

            usleep(50000);
        }

        $this->io->writeLine('Purged '.$total.' access logs');
    }
}
