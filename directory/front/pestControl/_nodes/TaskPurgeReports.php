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

use DecodeLabs\Terminus as Cli;

class TaskPurgeReports extends arch\node\Task
{
    public const MAX_LOOP = 250;

    public function execute(): void
    {
        $all = isset($this->request['all']);
        $threshold = '-'.$this->data->pestControl->getPurgeThreshold();
        $loop = $total = 0;

        while (++$loop < self::MAX_LOOP) {
            $total += $count = $this->data->pestControl->report->delete()
                ->chainIf(!$all, function ($query) use ($threshold) {
                    $query->where('date', '<', $threshold);
                })
                ->limit(100)
                ->execute();

            if (!$count) {
                break;
            }

            usleep(50000);
        }

        Cli::success('Purged '.$total.' reports');
    }
}
