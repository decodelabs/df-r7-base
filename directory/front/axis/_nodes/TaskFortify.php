<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\axis\_nodes;

use DecodeLabs\Terminus as Cli;
use df\arch;
use df\axis;

class TaskFortify extends arch\node\Task
{
    public const SCHEDULE = '0 2 * * *';
    public const SCHEDULE_AUTOMATIC = true;

    public function prepareArguments(): array
    {
        return Cli::$command
            ->addArgument('?unit', 'Unit name')
            ->addArgument('?fortify', 'Fortify task')
            ->toArray();
    }

    public function execute(): void
    {
        if (isset($this->request['unit'])) {
            $this->_runTasks($this->data->getUnit($this->request['unit']));
        } else {
            $probe = new axis\introspector\Probe();
            $units = $probe->probeStorageUnits();

            foreach ($units as $inspector) {
                if ($inspector->isVirtual()) {
                    continue;
                }

                $unit = $inspector->getUnit();
                $this->_runTasks($unit);
            }
        }
    }


    protected function _runTasks(axis\IUnit $unit)
    {
        if (isset($this->request['fortify'])
        && $unit->getUnitId() == $this->request['unit']) {
            axis\fortify\Base::factory(
                $unit,
                $this->request['fortify']
            )->dispatch();
            return;
        }

        $tasks = axis\fortify\Base::loadAll($unit);

        foreach ($tasks as $name => $task) {
            Cli::{'yellow'}($unit->getUnitId() . '/' . $name . ': ');
            $task->dispatch();
            Cli::newLine();
        }
    }
}
