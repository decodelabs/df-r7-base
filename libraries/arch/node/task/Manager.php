<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\arch\node\task;

use df;
use df\core;
use df\arch;
use df\halo;

class Manager implements arch\node\ITaskManager {

    use core\TManager;

    const REGISTRY_PREFIX = 'manager://task';

    protected $_captureBackground = false;

    public function launch($request, core\io\IMultiplexer $multiplexer=null, $user=null, $dfSource=false) {
        $request = arch\Request::factory($request);
        $path = df\Launchpad::$applicationPath.'/entry/';
        $path .= df\Launchpad::$environmentId.'.php';
        $args = ['task', $request];

        if($dfSource) {
            $args[] = '--df-source';
        }

        if($this->_captureBackground && !$multiplexer) {
            $application = df\Launchpad::getApplication();

            if($application instanceof core\application\Task) {
                $multiplexer = $application->getMultiplexer();
                return halo\process\Base::launchScript($path, $args, $multiplexer, $user);
            }
        }

        return halo\process\Base::launchScript($path, $args, $multiplexer, $user);
    }

    public function launchBackground($request, $user=null, $dfSource=false) {
        $request = arch\Request::factory($request);
        $path = df\Launchpad::$applicationPath.'/entry/';
        $path .= df\Launchpad::$environmentId.'.php';
        $args = ['task', $request];

        if($dfSource) {
            $args[] = '--df-source';
        }

        if($this->_captureBackground) {
            $application = df\Launchpad::getApplication();

            if($application instanceof core\application\Task) {
                $multiplexer = $application->getMultiplexer();
                return halo\process\Base::launchScript($path, $args, $multiplexer, $user);
            }
        }

        return halo\process\Base::launchBackgroundScript($path, $args, $user);
    }

    public function launchQuietly($request) {
        $application = df\Launchpad::getApplication();

        if($application instanceof core\application\Task) {
            return $this->invoke($request, core\io\Multiplexer::defaultFactory('memory'));
        } else {
            return $this->launchBackground($request);
        }
    }

    public function invoke($request, core\io\IMultiplexer $io=null) {
        $request = arch\Request::factory($request);
        $context = arch\Context::factory($request, 'Task', true);
        $node = arch\node\Base::factory($context);

        if(!$node instanceof arch\node\ITaskNode) {
            throw core\Error::{'arch/node/EDefinition'}(
                'Child node '.$request.' does not extend arch\\node\\Task'
            );
        }

        if($io) {
            $node->io = $io;
        }

        $node->dispatch();
        return $node->io;
    }

    public function initiateStream($request) {
        $context = $this->_getActiveContext();
        $token = $context->data->task->invoke->prepareTask($request);

        return $context->http->redirect(
            $context->uri->directoryRequest(
                '~/tasks/invoke?token='.$token,
                $context->uri->backRequest(null, true)
            )
        );
    }

    public function queue($request, $priority='medium') {
        $context = $this->_getActiveContext();

        $queue = $context->data->task->queue->newRecord([
                'request' => $request,
                'priority' => $priority
            ])
            ->save();

        return $queue['id'];
    }

    public function queueAndLaunch($request, core\io\IMultiplexer $multiplexer=null) {
        $id = $this->queue($request, 'medium');
        return self::launch('tasks/launch-queued?id='.$id, $multiplexer);
    }

    public function queueAndLaunchBackground($request) {
        $id = $this->queue($request, 'medium');
        return self::launchBackground('tasks/launch-queued?id='.$id);
    }

    public function getSharedIo() {
        $application = df\Launchpad::getApplication();

        if($application instanceof core\application\Task) {
            return $application->getMultiplexer();
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

        if($application instanceof core\IContextAware) {
            $context = $application->getContext();
        }

        if(!$context) {
            $context = arch\Context::factory();
        }

        return $context;
    }


    public function shouldCaptureBackgroundTasks(bool $flag=null) {
        if($flag !== null) {
            $this->_captureBackground = $flag;
            return $this;
        }

        return $this->_captureBackground;
    }
}