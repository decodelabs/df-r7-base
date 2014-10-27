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

    public $io;

    public function __construct(arch\IContext $context, arch\IController $controller=null) {
        parent::__construct($context, $controller);
        $this->_init();
    }

    protected function _init() {}


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


    public function extractCliArguments(array $args) {
        // Do nothing        
    }


// Dispatch
    public function dispatch() {
        if(!$this->io) {
            $this->io = $this->task->getSharedIo();
        }

        return parent::dispatch();
    }

    public function runChild($request) {
        $request = arch\Request::factory($request);
        $context = $this->context->spawnInstance($request, true);
        $action = arch\Action::factory($context);

        if(!$action instanceof self) {
            $this->throwError(500, 'Child action '.$request.' does not extend arch\\task\\Action');
        }

        return $action->dispatch();
    }
}