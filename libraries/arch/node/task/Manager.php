<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\node\task;

use DecodeLabs\Deliverance;
use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\R7\Legacy;

use DecodeLabs\Systemic;
use DecodeLabs\Terminus as Cli;
use DecodeLabs\Terminus\Session;
use df\arch;
use df\core;
use df\flex;
use df\link;

class Manager implements arch\node\ITaskManager
{
    use core\TManager;

    public const REGISTRY_PREFIX = 'manager://task';

    public function launch(
        $request,
        ?Session $session = null,
        $user = null,
        bool $dfSource = false,
        bool $decoratable = null
    ): bool {
        $request = arch\Request::factory($request);
        $path = Genesis::$hub->getApplicationPath() . '/entry/';
        $path .= Genesis::$environment->getName() . '.php';
        $args = [$request];

        if ($dfSource) {
            $args[] = '--df-source';
        }

        $command = Systemic::scriptCommand([$path, ...$args])
            ->setWorkingDirectory(Genesis::$hub->getApplicationPath())
            ->setUser($user);

        if ($session) {
            return $command->run();
        } else {
            return $command->capture()->wasSuccessful();
        }
    }

    public function launchBackground(
        $request,
        $user = null,
        bool $dfSource = false,
        bool $decoratable = null
    ) {
        $request = arch\Request::factory($request);
        $path = Genesis::$hub->getApplicationPath() . '/entry/';
        $path .= Genesis::$environment->getName() . '.php';
        $args = ['task', $request];

        if ($dfSource) {
            $args[] = '--df-source';
        }

        return Systemic::scriptCommand([$path, ...$args])
            ->setWorkingDirectory(Genesis::$hub->getApplicationPath())
            ->setUser($user)
            ->launch();
    }

    public function launchQuietly($request): void
    {
        if (Genesis::$kernel->getMode() === 'Task') {
            $session = Cli::getSession();
            $oldBroker = $session->getBroker();
            $session->setBroker(Deliverance::newBroker());

            $this->invoke($request);
            $session->setBroker($oldBroker);
        } else {
            $this->launchBackground($request);
        }
    }

    public function invoke($request): void
    {
        $request = arch\Request::factory($request);
        $context = arch\Context::factory($request, true);
        $node = arch\node\Base::factory($context);

        if (!$node instanceof arch\node\ITaskNode) {
            throw Exceptional::{'df/arch/node/Definition'}(
                'Child node ' . $request . ' does not extend arch\\node\\Task'
            );
        }

        $node->dispatch();
    }

    public function initiateStream($request): link\http\IResponse
    {
        $context = $this->_getActiveContext();
        $token = $context->data->task->invoke->prepareTask($request);

        return Legacy::$http->redirect(
            $context->uri->directoryRequest(
                '~/tasks/invoke?token=' . $token,
                $context->uri->backRequest(null, true)
            )
        );
    }

    public function queue($request, string $priority = 'medium'): flex\IGuid
    {
        $context = $this->_getActiveContext();

        $queue = $context->data->task->queue->newRecord([
                'request' => $request,
                'priority' => $priority
            ])
            ->save();

        return $queue['id'];
    }

    public function queueAndLaunch($request, ?Session $session = null): bool
    {
        $id = $this->queue($request, 'medium');
        return $this->launch('tasks/launch-queued?id=' . $id, $session);
    }

    public function queueAndLaunchBackground($request)
    {
        $id = $this->queue($request, 'medium');
        return $this->launchBackground('tasks/launch-queued?id=' . $id);
    }

    protected function _getActiveContext()
    {
        return Legacy::getContext();
    }
}
