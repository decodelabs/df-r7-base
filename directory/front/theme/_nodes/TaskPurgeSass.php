<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\theme\_nodes;

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\Terminus as Cli;

use df\arch;

class TaskPurgeSass extends arch\node\Task implements arch\node\IBuildTaskNode
{
    public const RUN_AFTER = true;

    public function execute(): void
    {
        $paths = [
            'sass',
            'node'
        ];

        foreach ($paths as $path) {
            $dir = Atlas::dir(Genesis::$hub->getLocalDataPath() . '/' . $path);

            if (!$dir->exists()) {
                continue;
            }

            Cli::{'brightMagenta'}('Purging ' . $path . ' cache: ');
            $dir->delete();
            Cli::success('done');
        }
    }
}
