<?php
/**
 * This file is part of the Decode r7 framework
 * @license http://opensource.org/licenses/MIT
 */
declare(strict_types=1);

namespace DecodeLabs\R7\Genesis\Kernel;

use df\core\app\runner\Daemon as DaemonRunner;

use DecodeLabs\Genesis\Kernel;
use DecodeLabs\R7\Genesis\KernelTrait;
use DecodeLabs\Terminus;

class Daemon implements Kernel
{
    /**
     * @use KernelTrait<DaemonRunner>
     */
    use KernelTrait;

    protected DaemonRunner $runner;


    /**
     * Initialize platform systems
     */
    public function initialize(): void
    {
        $this->runner = $this->loadRunner();

        Terminus::getCommandDefinition()
            ->addArgument('initiator', 'Daemon initiator')
            ->addArgument('daemon', 'Daemon name')
            ->addArgument('?command', 'Command to call');
    }

    /**
     * Get run mode
     */
    public function getMode(): string
    {
        return 'Daemon';
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
