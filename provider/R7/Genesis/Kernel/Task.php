<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis\Kernel;

use df\arch\Context;
use df\arch\DirectoryAccessController;
use df\arch\IAccess;
use df\arch\node\ITaskNode;
use df\arch\node\Base as NodeBase;
use df\arch\Request;
use df\core\app\runner\Task as TaskRunner;
use df\core\IDispatchAware;

use DecodeLabs\Exceptional;
use DecodeLabs\Genesis;
use DecodeLabs\Genesis\Kernel;
use DecodeLabs\R7\Genesis\KernelTrait;
use DecodeLabs\R7\Legacy;
use DecodeLabs\Terminus;

use Throwable;

class Task implements Kernel
{
    use KernelTrait;


    /**
     * Initialize platform systems
     */
    public function initialize(): void
    {
        Terminus::getCommandDefinition()
            ->addArgument('task', 'Task path')
            ->addArgument('--df-source', 'Source mode');


        // Legacy
        DirectoryAccessController::$defaultAccess = IAccess::ALL;
    }

    /**
     * Get run mode
     */
    public function getMode(): string
    {
        return 'Task';
    }

    /**
     * Run app
     */
    public function run(): void
    {
        set_time_limit(0);

        $args = Terminus::prepareArguments();

        /** @var Request */
        $request = Request::factory($args['task']);

        /** @var Context $context */
        $context = Context::factory(clone $request);
        Legacy::setActiveContext($context);

        $node = NodeBase::factory($context);


        // Prepare request arguments
        if ($node instanceof ITaskNode) {
            $args = $node->prepareArguments();

            foreach ($args as $key => $value) {
                if (
                    $key !== 'df-source' &&
                    $key !== 'task' &&
                    $value !== null &&
                    $value !== false
                ) {
                    $context->request->query->{$key} = $value;
                }
            }
        }


        // On dispatch
        foreach (Legacy::getRegistryObjects() as $object) {
            if ($object instanceof IDispatchAware) {
                $object->onAppDispatch($node);
            }
        }

        $node->dispatch();
    }
}
