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
use df\link;
use df\flex;

use DecodeLabs\Systemic;
use DecodeLabs\Systemic\Process\Result as ProcessResult;

class Manager implements arch\node\ITaskManager
{
    use core\TManager;

    const REGISTRY_PREFIX = 'manager://task';

    public function launch($request, core\io\IMultiplexer $multiplexer=null, $user=null, bool $dfSource=false): ProcessResult
    {
        $request = arch\Request::factory($request);
        $path = df\Launchpad::$app->path.'/entry/';
        $path .= df\Launchpad::$app->envId.'.php';
        $args = ['task', $request];

        if ($dfSource) {
            $args[] = '--df-source';
        }

        return Systemic::$process->newScriptLauncher($path, $args, null, $user)
            ->thenIf($multiplexer, function ($launcher) use ($multiplexer) {
                $multiplexer->exportToAtlasLauncher($launcher);
            })
            ->setDecoratable(!(bool)$user)
            ->launch();
    }

    public function launchBackground($request, $user=null, bool $dfSource=false)
    {
        $request = arch\Request::factory($request);
        $path = df\Launchpad::$app->path.'/entry/';
        $path .= df\Launchpad::$app->envId.'.php';
        $args = ['task', $request];

        if ($dfSource) {
            $args[] = '--df-source';
        }

        return Systemic::$process->newScriptLauncher($path, $args, null, $user)
            ->setDecoratable(!(bool)$user)
            ->launchBackground();
    }

    public function launchQuietly($request)
    {
        if (df\Launchpad::$runner instanceof core\app\runner\Task) {
            return $this->invoke($request, core\io\Multiplexer::defaultFactory('memory'));
        } else {
            return $this->launchBackground($request);
        }
    }

    public function invoke($request, core\io\IMultiplexer $io=null): core\io\IMultiplexer
    {
        $request = arch\Request::factory($request);
        $context = arch\Context::factory($request, 'Task', true);
        $node = arch\node\Base::factory($context);

        if (!$node instanceof arch\node\ITaskNode) {
            throw core\Error::{'arch/node/EDefinition'}(
                'Child node '.$request.' does not extend arch\\node\\Task'
            );
        }

        if ($io) {
            $node->io = $io;
        }

        $node->dispatch();
        return $node->io;
    }

    public function initiateStream($request): link\http\IResponse
    {
        $context = $this->_getActiveContext();
        $token = $context->data->task->invoke->prepareTask($request);

        return $context->http->redirect(
            $context->uri->directoryRequest(
                '~/tasks/invoke?token='.$token,
                $context->uri->backRequest(null, true)
            )
        );
    }

    public function queue($request, string $priority='medium'): flex\IGuid
    {
        $context = $this->_getActiveContext();

        $queue = $context->data->task->queue->newRecord([
                'request' => $request,
                'priority' => $priority
            ])
            ->save();

        return $queue['id'];
    }

    public function queueAndLaunch($request, core\io\IMultiplexer $multiplexer=null): ProcessResult
    {
        $id = $this->queue($request, 'medium');
        return $this->launch('tasks/launch-queued?id='.$id, $multiplexer);
    }

    public function queueAndLaunchBackground($request)
    {
        $id = $this->queue($request, 'medium');
        return $this->launchBackground('tasks/launch-queued?id='.$id);
    }

    public function getSharedIo(): core\io\IMultiplexer
    {
        $runner = df\Launchpad::$runner;

        if ($runner instanceof core\app\runner\Task) {
            return $runner->getMultiplexer();
        }

        $key = core\io\Multiplexer::REGISTRY_KEY.':task';

        if (!$output = df\Launchpad::$app->getRegistryObject($key)) {
            $output = core\io\Multiplexer::defaultFactory('task');
            df\Launchpad::$app->setRegistryObject($output);
        }

        return $output;
    }

    protected function _getActiveContext()
    {
        $runner = df\Launchpad::$runner;
        $context = null;

        if ($runner instanceof core\IContextAware) {
            $context = $runner->getContext();
        }

        if (!$context) {
            $context = arch\Context::factory();
        }

        return $context;
    }
}
