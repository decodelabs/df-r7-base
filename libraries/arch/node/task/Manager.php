<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */

namespace df\arch\node\task;

use DecodeLabs\Exceptional;
use DecodeLabs\R7\Legacy;

use df\arch;
use df\core;
use df\link;

class Manager implements arch\node\ITaskManager
{
    use core\TManager;

    public const REGISTRY_PREFIX = 'manager://task';

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

    protected function _getActiveContext()
    {
        return Legacy::getContext();
    }
}
