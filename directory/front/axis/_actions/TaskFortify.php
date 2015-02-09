<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\axis\_actions;

use df;
use df\core;
use df\apex;
use df\arch;
use df\axis;

class TaskFortify extends arch\task\Action {
    
    const SCHEDULE = '0 0 * * 1';
    const SCHEDULE_AUTOMATIC = true;

    public function execute() {
        $routines = [];

        $probe = new axis\introspector\Probe();
        $units = $probe->probeStorageUnits();

        foreach($units as $inspector) {
            if($inspector->isVirtual()) {
                continue;
            }

            $unit = $inspector->getUnit();
            $routines = axis\routine\Consistency::loadAll($unit);
            $count = count($routines);

            if(!$count) {
                continue;
            }

            $this->io->writeLine('Found '.$count.' consistency routine(s) in '.$inspector->getId());

            foreach($routines as $routine) {
                if(!$routine->canExecute()) {
                    continue;
                }

                $routine->setMultiplexer($this->io);
                $routine->execute();
            }

            $this->io->writeLine();
        }
    }
}