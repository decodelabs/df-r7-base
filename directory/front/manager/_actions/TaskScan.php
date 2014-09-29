<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\apex\directory\front\manager\_actions;

use df;
use df\core;
use df\apex;
use df\arch;

class TaskScan extends arch\task\Action {
    
    const SCHEDULE = '0 18 * * *';
    const SCHEDULE_AUTOMATIC = true;

    public function execute() {
        $this->response->write('Compiling task list...');

        // Fetch full file list
        $fileList = df\Launchpad::$loader->lookupFileListRecursive('apex/directory', ['php'], function($path) {
            return basename($path) == '_actions';
        });

        $total = $scheduled = 0;
        $schedules = [];
        $reset = isset($this->request->query->reset);

        // Filter to task list
        foreach($fileList as $key => $path) {
            $basename = substr(basename($path), 0, -4);

            if(substr($basename, 0, 4) != 'Task') {
                unset($fileList[$key]);
                continue;
            }

            $total++;
            $keyParts = explode('/', dirname($key));
            $class = 'df\\apex\\directory\\'.implode('\\', $keyParts).'\\'.$basename;
            $ref = new \ReflectionClass($class);

            if(!$ref->implementsInterface('df\\arch\\task\\IAction')) {
                continue;
            }

            $schedule = $class::getSchedule();

            if($schedule === null) {
                continue;
            }

            $scheduled++;
            array_pop($keyParts);

            if($keyParts[0] == 'front') {
                array_shift($keyParts);
            } else {
                $keyParts[0] = '~'.$keyParts[0];
            }

            $request = (string)arch\Request::factory(implode('/', $keyParts).'/'.$this->format->actionSlug(substr($basename, 4)))->getPath();

            $schedules[$request] = [
                'schedule' => $schedule,
                'environmentMode' => $class::getScheduleEnvironmentMode(),
                'priority' => $class::getSchedulePriority(),
                'automatic' => $class::shouldScheduleAutomatically(),
                'record' => null
            ];
        }

        $this->response->writeLine(' found '.$total.' tasks, '.$scheduled.' can be scheduled');


        if($reset) {
            // Reset
            $this->response->write('Resetting auto scheduled tasks...');

            $deleted = $this->data->task->schedule->delete()
                ->where('request', 'in', array_keys($schedules))
                ->execute();

            $this->response->writeLine(' '.$deleted.' found');
        } else {
            // Filter skippable
            $skip = 0;
            $skipList = $this->data->task->schedule->select('request')
                ->where('request', 'in', array_keys($schedules))
                ->where('isAuto', '=', false);

            foreach($skipList as $task) {
                unset($schedules[$task['request']]);
                $skip++;
            } 

            if($skip) {
                $this->response->writeLine('Skipping '.$skip.' as they are manually scheduled');
            }


            // Mix in updatable
            $updateList = $this->data->task->schedule->fetch()
                ->where('request', 'in', array_keys($schedules))
                ->where('isAuto', '=', true);

            foreach($updateList as $task) {
                $schedules[$task['request']]['record'] = $task;
            }
        }


        $this->response->writeLine();

        // Write
        foreach($schedules as $request => $set) {
            $scheduleParts = explode(' ', $set['schedule'], 5);

            if(isset($set['record'])) {
                $schedule = $set['record'];
            } else {
                $schedule = $this->data->task->schedule->newRecord([
                    'request' => $request,
                    'environmentMode' => $set['environmentMode'],
                    'priority' => $set['priority'],
                ]);
            }

            $schedule->import([
                'minute' => array_shift($scheduleParts),
                'hour' => array_shift($scheduleParts),
                'day' => array_shift($scheduleParts),
                'month' => array_shift($scheduleParts),
                'weekday' => array_shift($scheduleParts),
                'isLive' => $set['automatic']
            ]);

            if(!$schedule->hasChanged()) {
                $this->response->writeLine('Not updating '.$request.' because it hasn\'t changed');
                continue;
            }

            $this->response->writeLine('Scheduling '.$request.' at '.$set['schedule']);
            $schedule->save();
        }
    }
}