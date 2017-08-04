<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\cache\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskPurgeFileStores extends arch\node\Task {

    public function execute() {
        $this->io->write('Purging file stores...');
        core\cache\FileStore::purgeAll();
        $this->io->writeLine(' done');
    }
}
