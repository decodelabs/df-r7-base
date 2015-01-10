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

    protected $_captureBackground = false;

    public function launch($request, core\io\IMultiplexer $multiplexer=null, $environmentMode=null, $user=null) {
        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }
        
        $request = arch\Request::factory($request);
        $path = df\Launchpad::$applicationPath.'/entry/';
        $path .= df\Launchpad::$environmentId.'.'.$environmentMode.'.php';

        if($this->_captureBackground && !$multiplexer) {
            $application = df\Launchpad::getApplication();

            if($application instanceof core\application\Task) {
                $multiplexer = $application->getTaskResponse();
                return halo\process\Base::launchScript($path, ['task', $request], $multiplexer, $user);
            }
        }

        return halo\process\Base::launchScript($path, ['task', $request], $multiplexer, $user);
    }

    public function launchBackground($request, $environmentMode=null, $user=null) {
        $request = arch\Request::factory($request);

        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }

        $path = df\Launchpad::$applicationPath.'/entry/';
        $path .= df\Launchpad::$environmentId.'.'.$environmentMode.'.php';

        if($this->_captureBackground) {
            $application = df\Launchpad::getApplication();

            if($application instanceof core\application\Task) {
                $multiplexer = $application->getTaskResponse();
                return halo\process\Base::launchScript($path, ['task', $request], $multiplexer, $user);
            }
        }

        return halo\process\Base::launchBackgroundScript($path, ['task', $request], $user);
    }

    public function launchQuietly($request) {
        $application = df\Launchpad::getApplication();

        if($application instanceof core\application\Task) {
            return $this->invoke($request);
        } else {
            return $this->launchBackground($request);
        }
    }

    public function invoke($request) {
        $request = arch\Request::factory($request);
        $context = arch\Context::factory($request, 'Task', true);
        $action = arch\Action::factory($context);

        if(!$action instanceof IAction) {
            $context->throwError(500, 'Child action '.$request.' does not extend arch\\task\\Action');
        }

        $action->dispatch();
        return $action->io;
    }

    public function initiateStream($request, $environmentMode=null) {
        $context = $this->_getActiveContext();
        $token = $context->data->task->invoke->prepareTask($request, $environmentMode);
        
        return $context->http->redirect(
            $context->uri->directoryRequest(
                '~/tasks/invoke?token='.$token, 
                $context->uri->backRequest(null, true)
            )
        );
    }

    public function queue($request, $priority='medium', $environmentMode=null) {
        if($environmentMode === null) {
            $environmentMode = df\Launchpad::getEnvironmentMode();
        }

        $context = $this->_getActiveContext();
        $queue = $context->data->task->queue->newRecord([
                'request' => $request,
                'environmentMode' => $environmentMode,
                'priority' => $priority
            ])
            ->save();

        return $queue['id'];
    }

    public function queueAndLaunch($request, core\io\IMultiplexer $multiplexer=null, $environmentMode=null) {
        $id = $this->queue($request, 'medium', $environmentMode);
        return self::launch('manager/launch-queued?id='.$id, $multiplexer, $environmentMode);
    }

    public function queueAndLaunchBackground($request, $environmentMode=null) {
        $id = $this->queue($request, 'medium', $environmentMode);
        return self::launchBackground('manager/launch-queued?id='.$id, $environmentMode);
    }

    public function getSharedIo() {
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

    protected function _getActiveContext() {
        $application = df\Launchpad::getApplication();
        $context = null;

        if($application instanceof IDirectoryRequestApplication) {
            $context = $application->getContext();
        }

        if(!$context) {
            $context = arch\Context::factory();
        }

        return $context;
    }


    public function shouldCaptureBackgroundTasks($flag=null) {
        if($flag !== null) {
            $this->_captureBackground = (bool)$flag;
            return $this;
        }

        return $this->_captureBackground;
    }


    public function findChildrenIn($request) {
        $path = arch\Request::factory($request)->getLibraryPath().'/_actions';
        $output = [];
        
        foreach(df\Launchpad::$loader->lookupClassList($path) as $name => $class) {
            $requestParts = array_slice(explode('\\', $class), 3, -2);
            $requestParts[] = substr($name, 4);

            array_walk($requestParts, function(&$value) {
                $value = core\string\Manipulator::formatActionSlug($value);
            });

            $output[] = arch\Request::factory('~'.implode('/', $requestParts));
        }

        return $output;
    }
}