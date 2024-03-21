<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\cache\_nodes;

use DecodeLabs\Stash;
use DecodeLabs\Terminus as Cli;
use df\arch;
use df\core;

class TaskPurgeFileStores extends arch\node\Task
{
    public function execute(): void
    {
        Cli::{'yellow'}('Purging file stores: ');
        core\cache\FileStore::purgeAll();
        Stash::purgeFileStores();
        Cli::success('done');
    }
}
