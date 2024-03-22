<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\cache\_nodes;

use DecodeLabs\Stash;
use DecodeLabs\Terminus as Cli;
use df\arch;

class TaskPrune extends arch\node\Task
{
    public const SCHEDULE = '0 23 */2 * *';
    public const SCHEDULE_AUTOMATIC = true;

    public function execute(): void
    {
        Cli::{'yellow'}('FileStore: ');
        $count = Stash::pruneFileStores('1 week');
        Cli::success($count . ' removed');
    }
}
