<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\task;

use df;
use df\core;
use df\arch;

// Exceptions
interface IException {}


// Interfaces
interface IAction extends arch\IAction {
    public static function getSchedule();
    public static function getScheduleEnvironmentMode();
    public static function getSchedulePriority();
    public static function shouldScheduleAutomatically();

    public function extractCliArguments(core\cli\ICommand $command);
    public function runChild($request);
}


interface IManager extends core\IManager {
    public function launch($request, core\io\IMultiplexer $multiplexer=null, $environmentMode=null, $user=null);
    public function launchBackground($request, $environmentMode=null, $user=null);
    public function invoke($request);
    public function initiateStream($request, $environmentMode=null);
    public function queue($request, $priority='medium', $environmentMode=null);
    public function queueAndLaunch($request, core\io\IMultiplexer $multiplexer=null, $environmentMode=null);
    public function queueAndLaunchBackground($request, $environmentMode=null);
    public function getSharedIo();
    public function shouldCaptureBackgroundTasks($flag=null);
}