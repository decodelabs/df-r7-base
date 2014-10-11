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

class TaskPurge extends arch\task\Action {
    
    public function execute() {
        $this->task->shouldCaptureBackgroundTasks(true);
        $this->io->writeLine('Purging cache backends...');

        if(function_exists('opcache_reset')) {
            $this->io->writeLine('Opcache');
            opcache_reset();
        }

        $config = core\cache\Config::getInstance();

        foreach(df\Launchpad::$loader->lookupClassList('core/cache/backend') as $name => $class) {
            $this->io->writeLine($name);
            $options = new core\collection\Tree($config->getBackendOptions($name));
            $class::purgeAll($options);
        }
    }
}