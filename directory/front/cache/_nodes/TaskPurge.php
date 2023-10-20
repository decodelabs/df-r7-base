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

class TaskPurge extends arch\node\Task
{
    public function execute(): void
    {
        if (function_exists('opcache_reset')) {
            Cli::{'.green'}('Opcache');
            opcache_reset();
        }

        $config = CacheConfig::load();
        $isAll = isset($this->request['all']);

        foreach (Legacy::getLoader()->lookupClassList('core/cache/backend') as $name => $class) {
            Cli::{'.green'}($name);
            $options = $config->getBackendOptions($name);

            $isAll ?
                $class::purgeAll($options, Cli::getSession()) :
                $class::purgeApp($options, Cli::getSession());
        }
    }
}
