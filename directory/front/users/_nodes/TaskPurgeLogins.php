<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\users\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\user;

use DecodeLabs\Terminus as Cli;

class TaskPurgeLogins extends arch\node\Task
{
    public const SCHEDULE = '25 */2 * * *';
    public const SCHEDULE_AUTOMATIC = true;

    public function execute()
    {
        $total = 0;
        $unit = $this->data->user->login;

        while (true) {
            $items = $unit->select('id')

                ->where('date', '<', '-5 days')

                ->limit(200)
                ->toList('id');

            if (empty($items)) {
                break;
            }

            foreach ($items as $id) {
                $total += $unit->delete()
                    ->where('id', '=', $id)
                    ->execute();
            }

            usleep(10000);
        }

        Cli::deleteSuccess($total.' removed');
    }
}
