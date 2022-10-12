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

use DecodeLabs\Terminus as Cli;
use DecodeLabs\R7\Legacy;

class TaskPurge extends arch\node\Task
{
    public function execute(): void
    {
        if (function_exists('opcache_reset')) {
            Cli::{'.green'}('Opcache');
            opcache_reset();
        }

        $config = core\cache\Config::getInstance();
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
