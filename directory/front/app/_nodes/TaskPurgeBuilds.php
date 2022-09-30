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

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\Terminus as Cli;

class TaskPurgeBuilds extends arch\node\Task
{
    public const BUILD_DURATION = '5 minutes';

    public function execute()
    {
        $this->ensureDfSource();

        Cli::{'yellow'}('Purging old build folders: ');

        $appPath = Genesis::$hub->getApplicationPath();
        $buildDir = Atlas::dir($appPath.'/data/local/build');
        $all = isset($this->request['all']);
        $active = $this->filter['?active']->guid();

        if (!$buildDir->exists()) {
            Cli::success('0 found');
        } else {
            $checkTime = $this->date('-'.self::BUILD_DURATION)->toTimestamp();
            $del = 0;
            $keep = 0;

            foreach ($buildDir->scanDirs() as $name => $dir) {
                if ($name === $active) {
                    $keep++;
                    continue;
                }

                try {
                    $guid = flex\Guid::factory($name);
                } catch (\Throwable $e) {
                    $dir->delete();
                    $del++;
                    continue;
                }

                if ($all || $guid->getTime() < $checkTime) {
                    $dir->delete();
                    $del++;
                } else {
                    $keep++;
                }
            }

            if ($keep) {
                Cli::inlineNotice('kept '.$keep.', ');
            }

            Cli::deleteSuccess('deleted '.$del);

            if ($buildDir->isEmpty()) {
                $buildDir->delete();
            }
        }
    }
}
