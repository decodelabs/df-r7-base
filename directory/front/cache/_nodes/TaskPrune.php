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

use DecodeLabs\Terminus\Cli;

class TaskPrune extends arch\node\Task
{
    const SCHEDULE = '0 23 */2 * *';
    const SCHEDULE_AUTOMATIC = true;

    public function execute()
    {
        $config = core\cache\Config::getInstance();

        foreach (df\Launchpad::$loader->lookupClassList('core/cache/backend') as $name => $class) {
            Cli::{'yellow'}($name.': ');
            $options = $config->getBackendOptions($name);
            $count = (int)$class::prune($options);
            Cli::success($count.' removed');
        }

        Cli::{'yellow'}('FileStore: ');
        $count = core\cache\FileStore::prune('1 week');
        Cli::success($count.' removed');
    }
}
