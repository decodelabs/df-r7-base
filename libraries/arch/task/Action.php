<?php 
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\task;

use df;
use df\core;
use df\arch;
use df\halo;
    
abstract class Action extends arch\Action implements IAction {

    const SCHEDULE = null;
    const SCHEDULE_ENVIRONMENT_MODE = null;
    const SCHEDULE_PRIORITY = 'medium';
    const SCHEDULE_AUTOMATIC = false;

    const CHECK_ACCESS = false;

    public $io;

    public function __construct(arch\IContext $context) {
        parent::__construct($context);
        $this->init();
    }

    protected function init() {}


// Schedule
    public static function getSchedule() {
        $schedule = static::SCHEDULE;

        if(empty($schedule)) {
            $schedule = null;
        }

        return $schedule;
    }

    public static function getScheduleEnvironmentMode() {
        return static::SCHEDULE_ENVIRONMENT_MODE;
    }

    public static function getSchedulePriority() {
        return core\unit\Priority::factory(static::SCHEDULE_PRIORITY);
    }

    public static function shouldScheduleAutomatically() {
        return (bool)static::SCHEDULE_AUTOMATIC;
    }


    public function extractCliArguments(core\cli\ICommand $command) {
        // Do nothing        
    }


// Dispatch
    public function dispatch() {
        if(!$this->io) {
            $this->io = $this->task->getSharedIo();
        }

        return parent::dispatch();
    }

    public function runChild($request, $incLevel=true) {
        $request = $this->context->uri->directoryRequest($request);
        $context = $this->context->spawnInstance($request, true);
        $action = arch\Action::factory($context);

        if(!$action instanceof self) {
            $this->throwError(500, 'Child action '.$request.' does not extend arch\\task\\Action');
        }

        $action->io = $this->io;

        if($incLevel) {
            $this->io->incrementLineLevel();
        }

        $output = $action->dispatch();

        if($incLevel) {
            $this->io->decrementLineLevel();
        }
        
        return $output;
    }

    public function runChildQuietly($request) {
        $request = $this->context->uri->directoryRequest($request);
        $context = $this->context->spawnInstance($request, true);
        $action = arch\Action::factory($context);

        if(!$action instanceof self) {
            $this->throwError(500, 'Child action '.$request.' does not extend arch\\task\\Action');
        }

        $capture = $this->task->shouldCaptureBackgroundTasks();
        $this->task->shouldCaptureBackgroundTasks(false);

        $action->io = new core\io\Multiplexer([
            $output = new core\fs\MemoryFile()
        ]);

        $action->dispatch();
        $this->task->shouldCaptureBackgroundTasks($capture);

        return $output;
    }
}