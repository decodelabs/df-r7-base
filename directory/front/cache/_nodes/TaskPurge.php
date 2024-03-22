<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\cache\_nodes;

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\Stash;
use DecodeLabs\Terminus as Cli;

use df\arch;

class TaskPurge extends arch\node\Task
{
    public function execute(): void
    {
        if (function_exists('opcache_reset')) {
            Cli::{'.green'}('Opcache');
            opcache_reset();
        }

        Cli::{'.green'}('Stash');
        Stash::purge();

        // Delete
        $paths = [
            'Legacy cache' => Genesis::$hub->getLocalDataPath() . '/cache',
            'Legacy fileStore' => Genesis::$hub->getLocalDataPath() . '/filestore',
            'Intermediate fileStore' => Genesis::$hub->getLocalDataPath() . '/fileStore',
        ];

        foreach ($paths as $label => $path) {
            $dir = Atlas::dir($path);

            if ($dir->exists()) {
                Cli::{'.green'}($label);
                $dir->delete();
            }
        }
    }
}
