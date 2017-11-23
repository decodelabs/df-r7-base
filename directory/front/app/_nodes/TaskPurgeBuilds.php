<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\app\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;
use df\halo;
use df\flex;

class TaskPurgeBuilds extends arch\node\Task {

    const BUILD_DURATION = '5 minutes';

    public function execute() {
        $this->ensureDfSource();

        $this->io->write('Purging old build folders...');

        $appPath = df\Launchpad::$app->path;
        $buildDir = new core\fs\Dir($appPath.'/data/local/build');
        $all = isset($this->request['all']);
        $active = $this->filter['?active']->guid();

        if(!$buildDir->exists()) {
            $this->io->writeLine(' 0 found');
        } else {
            $checkTime = $this->date('-'.self::BUILD_DURATION)->toTimestamp();
            $del = 0;
            $keep = 0;

            foreach($buildDir->scanDirs() as $name => $dir) {
                if($name === $active) {
                    $keep++;
                    continue;
                }

                try {
                    $guid = flex\Guid::factory($name);
                } catch(\Throwable $e) {
                    $dir->unlink();
                    $del++;
                    continue;
                }

                if($all || $guid->getTime() < $checkTime) {
                    $dir->unlink();
                    $del++;
                } else {
                    $keep++;
                }
            }

            if($keep) {
                $this->io->write(' kept '.$keep.',');
            }

            $this->io->writeLine(' deleted '.$del);

            if($buildDir->isEmpty()) {
                $buildDir->unlink();
            }
        }
    }
}
