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

class Manager implements IManager {
    
    use core\TManager;

    const REGISTRY_PREFIX = 'manager://task';

    public function launch($request, core\io\IMultiplexer $multiplexer=null, $environmentMode=null) {
        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }
        
        $request = arch\Request::factory($request);
        $path = df\Launchpad::$applicationPath.'/entry/';
        $path .= df\Launchpad::$environmentId.'.'.$environmentMode.'.php';

        return halo\process\Base::launchScript($path, ['task', $request], $multiplexer);
    }

    public function launchBackground($request, $environmentMode=null) {
        $request = arch\Request::factory($request);

        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }

        $path = df\Launchpad::$applicationPath.'/entry/';
        $path .= df\Launchpad::$environmentId.'.'.$environmentMode.'.php';

        return halo\process\Base::launchBackgroundScript($path, ['task', $request]);
    }

    public function invoke($request) {
        $request = arch\Request::factory($request);
        $context = arch\Context::factory($request, 'Task');
        $action = arch\Action::factory($context);

        if(!$action instanceof IAction) {
            $context->throwError(500, 'Child action '.$request.' does not extend arch\\task\\Action');
        }

        $action->dispatch();
        return $action->response;
    }

    public function initiateStream($request, $environmentMode=null) {
        $application = df\Launchpad::getApplication();
        $context = null;

        if($application instanceof IDirectoryRequestApplication) {
            $context = $application->getContext();
        }

        if(!$context) {
            $context = arch\Context::factory();
        }

        $token = $context->data->task->invoke->prepareTask($request, $environmentMode);
        
        return $context->http->redirect(
            $context->directory->normalizeRequest(
                '~/tasks/invoke?token='.$token, 
                $context->directory->backRequest(null, true)
            )
        );
    }

    public function getResponse() {
        $application = df\Launchpad::getApplication();

        if($application instanceof core\application\Task) {
            return $application->getTaskResponse();
        }

        $key = core\io\Multiplexer::REGISTRY_KEY.':task';

        if(!$output = $application->getRegistryObject($key)) {
            $output = core\io\Multiplexer::defaultFactory('task');
            $application->setRegistryObject($output);
        }

        return $output;
    }
}