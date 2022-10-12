<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis\Kernel;

use df\core\app\runner\Task as TaskRunner;

use DecodeLabs\Genesis\Kernel;
use DecodeLabs\R7\Genesis\KernelTrait;
use DecodeLabs\Terminus;

class Task implements Kernel
{
    /**
     * @use KernelTrait<TaskRunner>
     */
    use KernelTrait;

    protected TaskRunner $runner;


    /**
     * Initialize platform systems
     */
    public function initialize(): void
    {
        $this->runner = $this->loadRunner();

        Terminus::getCommandDefinition()
            ->addArgument('task', 'Task path')
            ->addArgument('--df-source', 'Source mode');
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
        // Dispatch runner
        $this->runner->dispatch();
    }
}
