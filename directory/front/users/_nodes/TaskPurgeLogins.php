<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\users\_nodes;

use DecodeLabs\Terminus as Cli;

use df\arch;

class TaskPurgeLogins extends arch\node\Task
{
    public const SCHEDULE = '25 */2 * * *';
    public const SCHEDULE_AUTOMATIC = true;

    public function execute(): void
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

        Cli::deleteSuccess($total . ' removed');
    }
}
