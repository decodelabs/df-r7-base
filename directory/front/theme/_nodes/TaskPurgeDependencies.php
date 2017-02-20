<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\theme\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\aura;
use df\fuse;

class TaskPurgeDependencies extends arch\node\Task {

    public function execute() {
        $this->io->write('Purging theme dependencies...');

        core\fs\Dir::delete(fuse\Manager::getAssetPath());
        core\fs\Dir::delete(fuse\Manager::getManifestCachePath());

        $this->io->writeLine(' done');
    }
}