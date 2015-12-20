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

class TaskPurge extends arch\node\Task {

    public function execute() {
        $this->task->shouldCaptureBackgroundTasks(true);

        if(function_exists('opcache_reset')) {
            $this->io->writeLine('Opcache');
            opcache_reset();
        }

        $config = core\cache\Config::getInstance();
        $isAll = isset($this->request['all']);

        foreach(df\Launchpad::$loader->lookupClassList('core/cache/backend') as $name => $class) {
            $this->io->writeLine($name);
            $options = $config->getBackendOptions($name);

            $isAll ?
                $class::purgeAll($options) :
                $class::purgeApp($options);
        }
    }
}