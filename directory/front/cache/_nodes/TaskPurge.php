<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\cache\_nodes;

use DecodeLabs\Atlas;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Config\Cache as CacheConfig;
use DecodeLabs\R7\Legacy;
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

        $config = CacheConfig::load();

        foreach (Legacy::getLoader()->lookupClassList('core/cache/backend') as $name => $class) {
            Cli::{'.green'}($name);
            $options = $config->getBackendOptions($name);
            $class::purgeAll($options, Cli::getSession());
        }

        Stash::purge();

        // Delete
        $paths = [
            Genesis::$hub->getLocalDataPath() . '/filestore',
            Genesis::$hub->getLocalDataPath() . '/fileStore',
        ];

        foreach ($paths as $path) {
            $dir = Atlas::dir($path);

            if ($dir->exists()) {
                Cli::{'.green'}('Legacy file store');
                $dir->delete();
            }
        }
    }
}
