<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\sessions\_nodes;

use DecodeLabs\Terminus as Cli;
use df\arch;

use df\core;

class TaskCollectGarbage extends arch\node\Task
{
    public const SCHEDULE = '*/15 * * * *';
    public const SCHEDULE_AUTOMATIC = true;

    public const LIFETIME = 86400; // 24 hours
    public const CLOSE_THRESHOLD = '9 minutes';

    public function execute(): void
    {
        $startDate = new core\time\Date('now');

        $time = time() - static::LIFETIME;
        $total = $this->data->session->descriptor->select('COUNT(*) as total')
            ->where('accessTime', '<', $time)
            ->toValue('total');

        Cli::info($total . ' descriptors found');

        while (true) {
            $descriptors = $this->data->session->descriptor->select('id')
                ->where('accessTime', '<', $time)
                ->limit(100)
                ->toArray();

            if (empty($descriptors)) {
                break;
            }

            $nodeCount = 0;

            foreach ($descriptors as $descriptor) {
                $nodeCount += $this->data->session->node->delete()
                    ->where('descriptor', '=', $descriptor['id'])
                    ->execute();

                $this->data->session->descriptor->delete()
                    ->where('id', '=', $descriptor['id'])
                    ->execute();

                usleep(500);
            }

            usleep(50000);
            Cli::operative($nodeCount . ' nodes');

            if ($startDate->lt('-' . self::CLOSE_THRESHOLD)) {
                Cli::info('Reached time limit');
                return;
            }
        }
    }
}
