<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\cache\_actions;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskPrune extends arch\task\Action {
    
    const SCHEDULE = '0 23 */2 * *';
    const SCHEDULE_AUTOMATIC = true;

    public function execute() {
        $this->task->shouldCaptureBackgroundTasks(true);
        $this->io->writeLine('Pruning cache backends...');

        $config = core\cache\Config::getInstance();

        foreach(df\Launchpad::$loader->lookupClassList('core/cache/backend') as $name => $class) {
            $this->io->write($name.'... ');
            $options = $config->getBackendOptions($name);
            $count = (int)$class::prune($options);
            $this->io->writeLine($count.' stale items removed');
        }
    }
}