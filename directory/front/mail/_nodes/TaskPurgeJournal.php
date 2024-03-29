<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\mail\_nodes;

use DecodeLabs\Terminus as Cli;

use df\arch;

class TaskPurgeJournal extends arch\node\Task
{
    public const SCHEDULE = '0 3 * * *';
    public const SCHEDULE_AUTOMATIC = true;

    public function execute(): void
    {
        Cli::{'yellow'}('Purging mail journals: ');

        $deleted = $this->data->mail->journal->delete()
            ->where('expireDate', '!=', null)
            ->where('expireDate', '<', 'now')
            ->execute();

        Cli::success($deleted . ' deleted');
    }
}
