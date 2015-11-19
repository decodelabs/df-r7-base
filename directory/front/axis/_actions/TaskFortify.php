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

class TaskFortify extends arch\action\Task {

    const SCHEDULE = '0 2 * * *';
    const SCHEDULE_AUTOMATIC = true;

    public function extractCliArguments(core\cli\ICommand $command) {
        $args = [];

        foreach($command->getArguments() as $arg) {
            if(!$arg->isOption()) {
                $args[] = (string)$arg;
            }
        }

        if(isset($args[0])) {
            $this->request->query->unit = $args[0];

            if(isset($args[1])) {
                $this->request->query->routine = $args[1];
            }
        }
    }

    public function execute() {
        if(isset($this->request['unit'])) {
            $this->_runRoutines($this->data->getUnit($this->request['unit']));
        } else {
            $probe = new axis\introspector\Probe();
            $units = $probe->probeStorageUnits();

            foreach($units as $inspector) {
                if($inspector->isVirtual()) {
                    continue;
                }

                $unit = $inspector->getUnit();
                $this->_runRoutines($unit);
            }
        }
    }

    protected function _runRoutines($unit) {
        if(isset($this->request['routine'])
        && $unit->getUnitId() == $this->request['unit']) {
            $this->_runRoutine($unit->getRoutine(
                $this->request['routine']
            ));

            return;
        }

        $routines = axis\routine\Consistency::loadAll($unit);
        $count = count($routines);

        if(!$count) {
            return;
        }

        $this->io->writeLine('Found '.$count.' consistency routine(s) in '.$unit->getUnitId());

        foreach($routines as $routine) {
            $this->_runRoutine($routine);
        }

        $this->io->writeLine();
    }

    protected function _runRoutine($routine) {
        if(!$routine->canExecute()) {
            return;
        }

        $routine->setMultiplexer($this->io);
        $routine->execute();
    }
}