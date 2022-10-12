<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\apex\directory\front\tasks\_nodes;

use df;
use df\core;
use df\apex;
use df\arch;

use DecodeLabs\Dictum;
use DecodeLabs\Terminus as Cli;
use DecodeLabs\R7\Legacy;

class TaskScan extends arch\node\Task
{
    public function execute(): void
    {
        Cli::inlineInfo('Compiling task list: ');

        // Fetch full file list
        $fileList = Legacy::getLoader()->lookupFileListRecursive('apex/directory', ['php'], function ($path) {
            return basename($path) == '_nodes';
        });

        $total = $scheduled = 0;
        $schedules = $this->getFilteredTaskList($fileList, $total, $scheduled);
        Cli::operative('found '.$total.', '.$scheduled.' schedulable');

        $schedules = $this->updateSchedules($schedules);
        $this->writeSchedules($schedules);
    }

    protected function getFilteredTaskList(iterable $fileList, &$total=0, &$scheduled=0): array
    {
        $schedules = [];

        foreach ($fileList as $key => $path) {
            $basename = substr(basename($path), 0, -4);

            if (substr($basename, 0, 4) != 'Task') {
                continue;
            }

            $total++;
            $keyParts = explode('/', dirname($key));
            $class = 'df\\apex\\directory\\'.implode('\\', $keyParts).'\\'.$basename;
            $ref = new \ReflectionClass($class);

            if (!$ref->implementsInterface('df\\arch\\node\\ITaskNode')) {
                continue;
            }

            $schedule = $class::getSchedule();

            if ($schedule === null) {
                continue;
            }

            $scheduled++;
            array_pop($keyParts);

            if ($keyParts[0] == 'front') {
                array_shift($keyParts);
            } else {
                $keyParts[0] = '~'.$keyParts[0];
            }

            $request = (string)arch\Request::factory(implode('/', $keyParts).'/'.Dictum::actionSlug(substr($basename, 4)))->getPath();

            $schedules[$request] = [
                'schedule' => $schedule,
                'priority' => $class::getSchedulePriority(),
                'automatic' => $class::shouldScheduleAutomatically(),
                'record' => null
            ];
        }

        return $schedules;
    }

    protected function updateSchedules(array $schedules): array
    {
        $reset = isset($this->request['reset']);

        if ($reset) {
            // Reset
            Cli::{'yellow'}('Resetting auto scheduled tasks: ');

            $deleted = $this->data->task->schedule->delete()
                ->where('request', 'in', array_keys($schedules))
                ->orWhere('isAuto', '=', true)
                ->execute();

            Cli::deleteSuccess($deleted.' found');
        } else {
            // Filter skippable
            $skip = 0;
            $skipList = $this->data->task->schedule->select('request')
                ->where('request', 'in', array_keys($schedules))
                ->where('isAuto', '=', false);

            foreach ($skipList as $task) {
                unset($schedules[$task['request']]);
                $skip++;
            }

            if ($skip) {
                Cli::operative('Skipping '.$skip.' as they are manually scheduled');
            }


            // Mix in updatable
            $updateList = $this->data->task->schedule->fetch()
                ->where('request', 'in', array_keys($schedules))
                ->where('isAuto', '=', true);

            foreach ($updateList as $task) {
                $schedules[$task['request']]['record'] = $task;
            }


            // Delete old
            $delete = $this->data->task->schedule->select('request')
                ->where('request', '!in', array_keys($schedules))
                ->where('isAuto', '=', true);

            foreach ($delete as $schedule) {
                $this->data->task->schedule->delete()
                    ->where('request', '=', $schedule['request'])
                    ->execute();

                Cli::deleteSuccess('Deleted '.$schedule['request']);
            }
        }

        return $schedules;
    }


    protected function writeSchedules(array $schedules): void
    {
        $spaced = false;

        $lastRuns = $this->data->task->schedule->select('request', 'lastRun')
            ->toList('request', 'lastRun');

        // Write
        foreach ($schedules as $request => $set) {
            $scheduleParts = explode(' ', $set['schedule'], 5);

            if (isset($set['record'])) {
                $schedule = $set['record'];
            } else {
                $schedule = $this->data->task->schedule->newRecord([
                    'request' => $request,
                    'priority' => $set['priority'],
                ]);
            }

            if (isset($lastRuns[$request])) {
                $schedule->lastRun = $lastRuns[$request];
            }

            $schedule->import([
                'minute' => array_shift($scheduleParts),
                'hour' => array_shift($scheduleParts),
                'day' => array_shift($scheduleParts),
                'month' => array_shift($scheduleParts),
                'weekday' => array_shift($scheduleParts),
                'isLive' => $set['automatic']
            ]);

            if (!$schedule->hasChanged()) {
                continue;
            }

            if (!$spaced) {
                $spaced = true;
                Cli::newLine();
            }

            Cli::{'brightMagenta'}($request);
            Cli::write(' : ');
            Cli::{'brightYellow'}($set['schedule'].' ');
            Cli::{'yellow'}($set['priority'].' priority ');

            $schedule->save();
            Cli::success('done');
        }
    }
}
