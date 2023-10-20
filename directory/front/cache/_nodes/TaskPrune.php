<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\cache\_nodes;

use DecodeLabs\R7\Config\Cache as CacheConfig;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus as Cli;

use df\arch;
use df\core;

class TaskPrune extends arch\node\Task
{
    public const SCHEDULE = '0 23 */2 * *';
    public const SCHEDULE_AUTOMATIC = true;

    public function execute(): void
    {
        $config = CacheConfig::load();

        foreach (Legacy::getLoader()->lookupClassList('core/cache/backend') as $name => $class) {
            Cli::{'yellow'}($name . ': ');
            $options = $config->getBackendOptions($name);
            $count = (int)$class::prune($options);
            Cli::success($count . ' removed');
        }

        Cli::{'yellow'}('FileStore: ');
        $count = core\cache\FileStore::prune('1 week');
        Cli::success($count . ' removed');
    }
}
