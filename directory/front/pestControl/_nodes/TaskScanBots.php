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

class TaskScanBots extends arch\node\Task
{
    const SCHEDULE = '0 0 * * 1';
    const SCHEDULE_AUTOMATIC = true;

    public function execute()
    {
        Cli::{'yellow'}('Updating user agents: ');
        $count = 0;
        $missCounts = [];
        $botIds = [];

        foreach ($this->data->user->agent->fetch() as $agent) {
            $agent['isBot'] = $this->data->user->agent->isBot($agent['body']);

            if ($agent->hasChanged('isBot')) {
                $agent->save();
                $botIds[] = $agent['id'];
                $count++;
            }
        }

        Cli::success($count.' marked as bots');
        Cli::{'yellow'}('Updating seen counts: ');

        $list = $this->data->pestControl->missLog->select('id', 'miss')
            ->where('userAgent', 'in', $botIds);

        foreach ($list as $missLog) {
            $id = (string)$missLog['miss'];

            if (!isset($missCounts[$id])) {
                $missCounts[$id] = 0;
            }

            $missCounts[$id]++;
        }

        $count = 0;

        foreach ($missCounts as $id => $botCount) {
            $count += $this->data->pestControl->miss->update()
                ->express('botsSeen', 'botsSeen', '+', $botCount)
                ->where('id', '=', $id)
                ->execute();
        }

        Cli::success($count.' updated');
    }
}
