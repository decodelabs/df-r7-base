<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\tasks\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

use DecodeLabs\Terminus as Cli;

class TaskPurgeQueue extends arch\node\Task
{
    public const THRESHOLD = '2 hours';

    public function execute(): void
    {
        // Clear out old logs
        Cli::{'yellow'}('Clearing broken queued tasks: ');

        $count = $this->data->task->queue->delete()
            ->where('lockDate', '<', '-'.static::THRESHOLD)
            ->execute();

        try {
            $this->data->task->queue->update(['status' => 'lagging'])
                ->where('lockDate', '<', '-30 minutes')
                ->execute();
        } catch (\Exception $e) {
        }

        Cli::{$count ? 'deleteSuccess' : 'success'}($count.' tasks');
    }
}
